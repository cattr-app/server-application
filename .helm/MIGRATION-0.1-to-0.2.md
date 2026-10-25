# Migrating from chart 0.1.x to 0.2.x

Chart 0.2 is a rewrite for the Cattr 4.1 runtime. An in-place `helm upgrade`
of the same release works, but **files are not migrated automatically** and
several values changed. Plan a short downtime.

## What changes

| | 0.1.x | 0.2.x |
|---|---|---|
| Image | `registry.git.amazingcat.net/cattr/core/app:latest` | `ghcr.io/cattr-app/server:<appVersion>` |
| Workload | one container, role `all` (migrations in every Pod) | `web`, `queue`, `scheduler` containers + setup Job |
| `replicaCount` | yes | removed (pvc mode is single-replica) |
| Files | PVCs `<fullname>-screenshots` / `-attachments` mounted at `storage/app/uploads/*` | one PVC `<fullname>-storage` with the paths the app actually uses |
| ConfigMap `<fullname>` | yes | removed (env is inline) |
| `APP_KEY` | generated randomly when unset | never generated; kept from the existing Secret |
| `REVERB_APP_SECRET` | baked into the image | required value (`cattr.reverbAppSecret`) |
| DB passwords | defaults `cattr` / `cattrroot` in values | no defaults; kept from the existing Secret on upgrade |
| Built-in MySQL | on by default, `mysql:8.0`, no PVC protection | opt-in demo (`mysql.enabled: true`), `mysql:8.4.13`, non-root, PVC kept |
| Ingress | on by default, `ingress.hostname` | off by default, `ingress.hosts[]` |
| Values | free-form | `values.schema.json` rejects unknown (0.1.x) keys |

**Chart 0.1.x never persisted uploaded files.** Its PVCs were mounted at
`storage/app/uploads/screenshots` and `storage/app/uploads/attachments`, but
Cattr writes `storage/app/screenshots` and `storage/app/attachments`, which
lived only in the running Pod. They are lost whenever that Pod is replaced —
so copy them out of the **running** Pod before upgrading, and do not restart
it before that. The 0.1.x PVCs are deleted by the upgrade; they contain no
application data (check with the command in step 2).

## 1. Back up the database

The dump is mandatory: it is the only way back to 0.1.x (see
[Rollback](#rollback)). The setup Job migrates the schema, and with the
built-in MySQL the upgrade moves the data directory from MySQL 8.0 to 8.4,
which MySQL 8.0 cannot open again (there is no in-place downgrade).

Built-in MySQL:

```sh
NS=cattr; REL=cattr; FULL=cattr-cattr-server   # adjust: <release>-cattr-server
kubectl -n $NS exec deploy/$FULL-mysql -- sh -c \
  'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --routines --triggers "$MYSQL_DATABASE"' \
  > cattr-backup.sql
```

External database: use your usual backup. Also save the Secret, it holds
`APP_KEY`:

```sh
kubectl -n $NS get secret $FULL -o yaml > cattr-secret-backup.yaml
```

## 2. Copy the files out of the running Pod

```sh
POD=$(kubectl -n $NS get pod -l app.kubernetes.io/instance=$REL,app.kubernetes.io/component=app -o name | head -1)
kubectl -n $NS exec $POD -- sh -c 'cd /opt/cattr/app/storage/app && find attachments screenshots -type f | wc -l; find uploads -type f | wc -l'
mkdir -p cattr-files
kubectl -n $NS cp ${POD#pod/}:/opt/cattr/app/storage/app/attachments cattr-files/attachments
kubectl -n $NS cp ${POD#pod/}:/opt/cattr/app/storage/app/screenshots cattr-files/screenshots
find cattr-files -type f | wc -l   # must match the first count
```

## 3. Protect the MySQL volume (built-in MySQL only)

0.1.x did not mark the MySQL PVC as kept. Annotate it before upgrading so a
mistake cannot delete the database:

```sh
kubectl -n $NS annotate pvc $FULL-mysql helm.sh/resource-policy=keep --overwrite
```

## 4. Switch the Deployment strategy (Helm 4 / server-side apply)

0.2 uses `strategy: Recreate`. With client-side apply (Helm 3, or Helm 4
for releases installed by Helm 3) the chart clears the old `rollingUpdate`
itself. With server-side apply (releases installed by Helm 4) the upgrade
fails with `spec.strategy.rollingUpdate: Forbidden`; switch the strategy first
(this does not restart the Pod):

```sh
kubectl -n $NS patch deployment $FULL --type=json \
  -p='[{"op":"replace","path":"/spec/strategy","value":{"type":"Recreate"}}]'
```

## 5. Upgrade

Write a new values file (do not use `--reuse-values`). APP_KEY and the DB
credentials are read from the existing Secret when left empty:

```yaml
cattr:
  appUrl: https://cattr.example.com
  reverbAppSecret: "<openssl rand -hex 32>"   # new, required once
# Built-in MySQL: keep it explicitly (default is now off)
mysql:
  enabled: true
# ...or an external database:
# database:
#   host: mysql.example.internal
setup:
  admin:
    enabled: false        # the administrator already exists
ingress:                  # if you used the 0.1.x Ingress
  enabled: true
  className: nginx
  hosts:
    - host: cattr.example.com
      paths: [{path: /, pathType: Prefix}]
```

```sh
helm upgrade $REL ./.helm -n $NS -f values-0.2.yaml --wait --timeout 15m
```

The `pre-upgrade` setup Job runs the migrations; then the Pod is recreated.
With the built-in MySQL the image changes from `mysql:8.0` to `mysql:8.4.13`
(in-place upgrade of the data directory, done by MySQL on first start) and it
now runs as UID 999 without root.

If the render fails with "the release still runs the built-in MySQL
Deployment", you left `mysql.enabled` at its new default `false`: set it to
`true`, or migrate the dump to an external database and set
`mysql.acknowledgeRemoval: true`.

## 6. Restore the files

```sh
POD=$(kubectl -n $NS get pod -l app.kubernetes.io/instance=$REL,app.kubernetes.io/component=app -o name | head -1)
kubectl -n $NS cp cattr-files/attachments ${POD#pod/}:/opt/cattr/app/storage/app/ -c web
kubectl -n $NS cp cattr-files/screenshots ${POD#pod/}:/opt/cattr/app/storage/app/ -c web
kubectl -n $NS exec ${POD#pod/} -c web -- sh -c 'cd /opt/cattr/app/storage/app && find attachments screenshots -type f | wc -l'
```

Files are written as UID 10000 onto the new PVC `<fullname>-storage`.

## 7. Verify

- `kubectl -n $NS get secret $FULL -o jsonpath='{.data.APP_KEY}'` equals the backup.
- `kubectl -n $NS logs job/$FULL-setup -c setup` ends with `DONE` (no `FAIL`).
- Log in with an existing user; open a task attachment and a screenshot.
- `kubectl -n $NS get pod` shows `3/3` containers; queue jobs are processed
  (e.g. attachment upload moves from processing to available).

## Rollback

`helm rollback` alone does not return to a working 0.1.x:

- **Built-in MySQL:** the upgrade moved the kept PVC `<fullname>-mysql` to
  MySQL 8.4. A rollback starts `mysql:8.0` on that 8.4 data directory, which
  MySQL does not support (no in-place downgrade): the server refuses to start
  ("Invalid MySQL server downgrade"). The only way back is to restore the
  pre-upgrade `mysqldump` from step 1 into a **fresh** MySQL 8.0 data
  directory on a new, empty PVC.
- **External database:** the setup Job ran the 0.2 migrations, so restore the
  database backup from step 1 before 0.1.x runs against it.
- Neither path restores the deleted 0.1.x PVCs (they held no application
  data) or the files on `<fullname>-storage`; copy the files from step 2 back
  into the 0.1.x Pod.

Built-in MySQL, step by step. You need the 0.1.x chart (e.g. checked out from
the 0.1.x release) as `./chart-0.1`, your 0.1.x values file
`values-0.1.yaml` (with the same `database.*` credentials as before) and the
backups from step 1.

1. Create an empty PVC for the 8.0 data directory with the same size and
   StorageClass as `<fullname>-mysql` (read from the existing claim below).
   Leave `<fullname>-mysql` untouched: it keeps the 8.4 data in case you go
   forward again.

   ```sh
   SIZE=$(kubectl -n $NS get pvc $FULL-mysql -o jsonpath='{.spec.resources.requests.storage}')
   SC=$(kubectl -n $NS get pvc $FULL-mysql -o jsonpath='{.spec.storageClassName}')
   echo "size=$SIZE storageClass=${SC:-<none>}"   # check both before applying
   # Omit storageClassName when the original claim has none: an empty string
   # would disable dynamic provisioning instead of using the default class.
   SC_LINE=""; [ -n "$SC" ] && SC_LINE="  storageClassName: $SC"

   kubectl -n $NS apply -f - <<EOF
   apiVersion: v1
   kind: PersistentVolumeClaim
   metadata:
     name: $FULL-mysql-80
   spec:
     accessModes: [ReadWriteOnce]
   $SC_LINE
     resources:
       requests:
         storage: $SIZE
   EOF
   kubectl -n $NS get pvc $FULL-mysql-80   # must become Bound (or Pending until first use with WaitForFirstConsumer)
   ```

2. Deploy 0.1.x on that PVC with the application scaled to zero, so it does
   not migrate or seed the empty database before the restore. 0.1.x generates
   a new random `APP_KEY` when `cattr.appKey` is empty: pass the key from the
   Secret backup (0.1.x expects it without the `base64:` prefix).

   ```sh
   APP_KEY=$(awk '$1 == "APP_KEY:" {print $2}' cattr-secret-backup.yaml | base64 -d)
   helm upgrade $REL ./chart-0.1 -n $NS -f values-0.1.yaml \
     --set mysql.persistence.existingClaim=$FULL-mysql-80 \
     --set cattr.appKey="${APP_KEY#base64:}" \
     --set replicaCount=0 --wait --timeout 15m
   ```

   (Use `helm upgrade` with the 0.1.x chart, not `helm rollback`: a rollback
   reuses the old values, which point MySQL at the 8.4 PVC.)

3. Wait until MySQL 8.0 has initialised the empty data directory, then load
   the dump:

   ```sh
   kubectl -n $NS rollout status deploy/$FULL-mysql
   kubectl -n $NS exec -i deploy/$FULL-mysql -- sh -c \
     'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < cattr-backup.sql
   ```

4. Start the application again (same command with your usual
   `replicaCount`, default 1) and copy the files from step 2 back into the
   Pod:

   ```sh
   helm upgrade $REL ./chart-0.1 -n $NS -f values-0.1.yaml \
     --set mysql.persistence.existingClaim=$FULL-mysql-80 \
     --set cattr.appKey="${APP_KEY#base64:}" \
     --set replicaCount=1 --wait --timeout 15m
   ```

5. Once 0.1.x works, `<fullname>-mysql` (8.4 data) and `<fullname>-storage`
   can be deleted by hand; both are kept by Helm.

External database: restore the backup from step 1 into the database (drop and
recreate it, or restore into a new database and point `database.*` at it),
then deploy 0.1.x with `values-0.1.yaml` and the `APP_KEY` from the Secret
backup as above, and copy the files back.
