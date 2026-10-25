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

`helm rollback $REL <0.1.x revision>` restores the 0.1.x objects, but not the
deleted 0.1.x PVCs (they held no application data) and not the files on
`<fullname>-storage`; the database stays migrated. To return to 0.1.x fully,
restore the database backup and copy the files back into the 0.1.x Pod.
