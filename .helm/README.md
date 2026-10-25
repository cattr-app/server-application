# cattr-server Helm chart

Chart `0.2.x` deploys the Cattr 4.1 runtime image (`ghcr.io/cattr-app/server`:
Caddy + Laravel Octane + Reverb, supervised by Pebble) with explicit roles.
The chart is versioned independently of the application; `appVersion` is the
image tag used by default (`image.tag` overrides it, `image.digest` pins it).

Upgrading from chart `0.1.x`: read [MIGRATION-0.1-to-0.2.md](MIGRATION-0.1-to-0.2.md)
first. Files are not carried over automatically.

## Quick start

```sh
# Generate the two application secrets once and keep them (e.g. in your secret store).
APP_KEY="base64:$(openssl rand -base64 32)"
REVERB_SECRET="$(openssl rand -hex 32)"

helm install cattr ./.helm -n cattr --create-namespace \
  --set cattr.appUrl=https://cattr.example.com \
  --set cattr.appKey="$APP_KEY" \
  --set cattr.reverbAppSecret="$REVERB_SECRET" \
  --set database.host=mysql.example.internal \
  --set database.username=cattr \
  --set database.password='...' \
  --set setup.admin.email=admin@example.com \
  --set setup.admin.password='...' \
  --wait --timeout 15m
```

Use `--timeout 15m`: the setup Job (migrations) runs inside the Helm
operation and a first install on slow storage can exceed Helm's default 5m.

Without Ingress/HTTPRoute the chart is reachable through Service
`<fullname>` (port 80 -> Caddy 8080), e.g.
`kubectl -n cattr port-forward svc/cattr-cattr-server 8080:80`.

## Architecture

| | `storage.type: pvc` (default) | `storage.type: s3` |
|---|---|---|
| Workloads | Deployment `<fullname>`: one Pod with containers `web`, `queue`, `scheduler` | Deployments `<fullname>-web`, `<fullname>-queue`, `<fullname>-scheduler` |
| Replicas | always 1, `strategy: Recreate` | web 1 (see Scaling), queue `queue.replicas`, scheduler always 1 (Recreate) |
| Files | one RWO PVC `<fullname>-storage` shared by the containers | S3-compatible bucket |
| Cache / locks | file cache on the PVC, or Redis if `redis.enabled` | external Redis (required) |
| Reverb for queue/scheduler | `127.0.0.1:8081` (same Pod) | Service `<fullname>-reverb:8081` -> web Pods |
| Nodes | single node (RWO volume) | multi-node |

Roles (container `args`): `web` runs Caddy, Octane and Reverb (Caddy proxies
`/app/*` WebSocket upgrades to Reverb on loopback), `queue` runs `queue:work`,
`scheduler` runs supercronic -> `schedule:run` every minute. No application
Pod runs migrations or creates administrators.

`s3` mode needs a Cattr version with S3 storage support (separate change; the
`4.1.0-alpha.2` image stores files on the local disk only). The chart renders
and lints it, but it is integration-tested only once that version exists.

### Setup Job, migrations and failures

The Job `<fullname>-setup` runs the `migrate` role (migrations +
`InitialSeeder`) and, when `setup.admin.enabled` and both email and password
are set, `provision` (`cattr:make:admin`; skipped if the email exists).

- It is a Helm hook `pre-install,pre-upgrade` (weight 0,
  `before-hook-creation`, `backoffLimit: 0`, `activeDeadlineSeconds: 1800`).
  Helm creates hooks one at a time, so two migrations never run in parallel
  for one release.
- If it fails as a `pre-install`/`pre-upgrade` hook (the default with an
  external database), `helm install/upgrade` fails **before** any workload is
  changed: the old Pods keep running the old version, the release is marked
  `failed`. Inspect `kubectl logs job/<fullname>-setup`, fix the cause and run
  the upgrade again (or `helm rollback`). The Job is kept until the next
  attempt; its hook Secret `<fullname>-setup` is kept after a failure too.
- Every application Pod starts with init container `wait-for-migrations`
  (`php artisan migrate:status --pending=1`): new code never starts against a
  schema that is missing its migrations, e.g. when hooks are disabled.
- Migrations are not reverted by `helm rollback`; the previous application
  version must be compatible with the newer schema (Cattr migrations are
  additive within a minor release). Restore the database backup to go back
  further.
- The Job's init container `wait-for-database` retries until the database
  accepts a login (bounded by `setup.activeDeadlineSeconds`), so a database
  that is still starting does not fail the setup.
- During an upgrade the old Pods keep serving while the migration runs.
- With the built-in demo MySQL the install hook is `post-install` (the
  database does not exist before install). Helm runs post-install hooks only
  after `--wait` finished, but the app Pods wait for those migrations: install
  the demo **without `--wait`** (Helm still waits for the setup Job) and use
  `kubectl rollout status deploy/<fullname> --timeout 15m`. Upgrades use
  `pre-upgrade` and work with `--wait`. Because the workloads already exist
  when the post-install Job runs, a failed first install is **not** left
  unchanged: the release is marked `failed`, the app Pods stay in
  `wait-for-migrations` if migrations failed, and if only the administrator
  provisioning failed they start without that administrator. Check
  `kubectl logs job/<fullname>-setup` and run `helm upgrade` again.
- `setup.hook: false` renders a plain Job `<fullname>-setup-<revision>` with
  `ttlSecondsAfterFinished`, for tools that skip hooks. Nothing then prevents
  two revisions' Jobs from running at the same time: let one finish first.
  The app Pods are gated on migrations only, so if just the administrator
  provisioning fails they start without that administrator; check the Job.

### Restarts and probes

| Container | Probes |
|---|---|
| web | startup `GET /actuator/health/liveness` (5s x 60), readiness `GET /api/status`, liveness `GET /actuator/health/liveness` (20s, timeout 5s, 6 failures) |
| queue, scheduler | liveness `pebble services <name>` reports `active` |

`/api/status` answers from the application without a database query;
`wait-for-migrations` is what checks the database before start. A database
outage therefore does not restart Pods; requests fail until it is back.

`cattr.restartPolicy` sets `CATTR_RESTART_POLICY`. With `shutdown` (default)
a crashed queue worker, Reverb or scheduler stops Pebble (exit code 10), so
Kubernetes restarts the container and counts the restart. With `restart`
Pebble restarts it in place. Images up to `4.1.0-alpha.2` ignore the variable
and always restart in place (the liveness probe covers a service stuck in
backoff).

Termination: `terminationGracePeriodSeconds: 45` (preStop `sleep 5` on web,
then Pebble stops services with SIGTERM and a 30s kill delay, letting the
queue worker finish its current job).

### Filesystem and security

Pods run as UID/GID 10000 with `runAsNonRoot`, `fsGroup: 10000`
(`OnRootMismatch`), seccomp `RuntimeDefault`; containers drop all
capabilities, disallow privilege escalation and use a read-only root
filesystem. The namespace can enforce the `restricted` Pod Security Standard
(the demo MySQL complies too). No ServiceAccount token is mounted. The setup
Job does not use the chart ServiceAccount (`serviceAccount.create`): as a
pre-install hook it runs before that account exists, so it runs as the
namespace `default` account, also without a token.

| Path below `/opt/cattr/app` | `pvc` mode | `s3` mode |
|---|---|---|
| `storage/app/screenshots`, `storage/app/attachments` | PVC (subPath) | S3; local dir is node-local emptyDir |
| `storage/app/tmp` (uploads in flight), `storage/app/public/reports` | PVC | emptyDir |
| `storage/framework/cache` (file cache, locks) | PVC | emptyDir (Redis holds cache) |
| `storage/framework/{sessions,views}`, `storage/logs`, `bootstrap/cache`, `/tmp`, `/opt/cattr/.pebble` | per-container emptyDir | per-container emptyDir |

Each container has one emptyDir (`storage.runtime.sizeLimit`, default 2Gi)
seeded by the `prepare` init container, which also creates the subPath
directories on the PVC with the right owner.

The PVC carries `helm.sh/resource-policy: keep` and
`argocd.argoproj.io/sync-options: Delete=false`; `storage.pvc.existingClaim`
uses your own claim instead.

## Secrets

Nothing is generated randomly. Each chart-managed value resolves as: value in
`values` -> value already stored in the chart Secret `<fullname>` (kept
byte-for-byte across upgrades and rollbacks) -> render fails with the name of
the missing value. The chart Secret is kept on `helm uninstall`.

| Value | Secret key | Format |
|---|---|---|
| `cattr.appKey` | `APP_KEY` | Laravel key, `base64:` + base64 of 32 random bytes (`php artisan key:generate --show` or `printf 'base64:%s' "$(openssl rand -base64 32)"`). The prefix is added when missing. Other lengths fail the render. |
| `cattr.reverbAppSecret` | `REVERB_APP_SECRET` | any random string (`openssl rand -hex 32`) |
| `database.username` / `database.password` | `DB_USERNAME` / `DB_PASSWORD` | |
| `database.rootPassword` | `MYSQL_ROOT_PASSWORD` | demo MySQL only |
| `redis.password` | `REDIS_PASSWORD` | optional |
| `storage.s3.accessKeyId` / `secretAccessKey` | `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` | optional (ambient credentials otherwise) |
| `setup.admin.password` | `APP_ADMIN_PASSWORD` (hook Secret `<fullname>-setup` only) | at least 6 characters |

Caller-managed Secrets replace the values: `cattr.existingSecret` (keys
`APP_KEY`, `REVERB_APP_SECRET`, renamable via `cattr.existingSecretKeys`),
`database.existingSecret` (`DB_USERNAME`, `DB_PASSWORD`, `MYSQL_ROOT_PASSWORD`),
`redis.existingSecret`, `storage.s3.existingSecret`,
`setup.admin.existingSecret`.

Pods carry `checksum/secret` of the chart-managed Secret data, so changing a
value there rolls the Pods. A change in an existing Secret does not: run
`kubectl -n <ns> rollout restart deployment -l app.kubernetes.io/instance=<release>`.

Back up `APP_KEY`: without it encrypted data cannot be read.

## Database and Redis

`database.host` (MySQL 8.x / MariaDB) is required. With binary logging
enabled (the MySQL 8 default) the database user needs
`log_bin_trust_function_creators=1` (server option or `SET GLOBAL`), or the
migrations that create triggers fail with error 1419 ("You do not have the
SUPER privilege and binary logging is enabled"); the demo MySQL sets it. `mysql.enabled: true`
deploys a single MySQL Pod (`mysql:8.4.13`, PVC `<fullname>-mysql` with
`resource-policy: keep`) for demos and evaluation only: no backups, no
replication. Releases upgraded from chart 0.1.x that still run the built-in
MySQL fail to render with `mysql.enabled: false` unless
`mysql.acknowledgeRemoval: true`.

`redis.enabled` points the cache (`CACHE_STORE`/`CACHE_DRIVER=redis`) to an
external Redis; required in `s3` mode. The queue stays on the database.

## Networking

Service `<fullname>` (port `service.port`, default 80) -> Caddy 8080 serves
HTTP, static files and the Reverb WebSocket upgrade on `/app/*`. Octane (8090)
is never exposed.

- **Ingress** (`ingress.enabled`, any controller; `className: ""` uses the
  cluster default). For ingress-nginx raise the upload limit and WebSocket
  timeouts:
  ```yaml
  ingress:
    annotations:
      nginx.ingress.kubernetes.io/proxy-body-size: 512m
      nginx.ingress.kubernetes.io/proxy-read-timeout: "3600"
      nginx.ingress.kubernetes.io/proxy-send-timeout: "3600"
  ```
- **Gateway API** (`httpRoute.enabled`, can be combined with Ingress):
  `gateway.networking.k8s.io/v1` HTTPRoute with rule `PathPrefix /app`
  (`timeouts.websocket`, default `0s` = disabled) and `/`
  (`timeouts.request`, default `300s`); set either to `""` to omit it. The
  render fails if the cluster does not serve HTTPRoute; for `helm template`
  pass `--api-versions gateway.networking.k8s.io/v1/HTTPRoute`.
- **TLS offload**: terminate TLS at the Ingress/Gateway and set
  `cattr.appUrl` to the external `https://` URL; generated links and the
  frontend use it. Caddy accepts `X-Forwarded-*` from private address ranges.
- **NetworkPolicy** (`networkPolicy.enabled`): 8080 from
  `networkPolicy.httpFrom` (default anywhere), Reverb 8081 only from this
  release's queue/scheduler/setup Pods, everything else (8090) denied;
  queue/scheduler/setup Pods accept no ingress.
- `/actuator/health/*` and `/actuator/prometheus` are unauthenticated; block
  `/actuator` at the edge if they must not be public.

## Scaling

`pvc` mode is single-replica by design: the RWO volume and file locks are
shared by the containers of one Pod. In `s3` mode `queue.replicas` scales
workers independently, the scheduler stays at one. `web.replicas > 1` fails
the render unless `web.unsupportedMultiReplica: true`: application settings
are cached per Pod by Octane (changes reach other Pods only after their
restart), and Reverb broadcasts from queue/scheduler reach only the web Pod
the Reverb Service picked (Reverb horizontal scaling needs Redis pub/sub,
not configured by the chart). PDBs (`web.pdb`, `queue.pdb`) are created only
in `s3` mode with more than one replica.

## GitOps

- **Flux** (HelmRelease): runs hooks; set `spec.timeout: 15m`
  and `install.remediation`/`upgrade.remediation` as you prefer — a failed
  setup leaves the old version running.
- **Argo CD**: maps Helm hooks to sync hooks (`pre-install`/`pre-upgrade` ->
  `PreSync`); `lookup` returns nothing under Argo CD, so always provide
  secrets from values or `existingSecret` (recommended). `post-install` maps
  to `PostSync`, which waits for healthy resources, so the demo MySQL first
  sync stalls the same way as `helm install --wait`: use an external
  database with Argo CD.
- Commit `existingSecret` references, not secret values.

## Install, upgrade, rollback, uninstall

```sh
helm upgrade --install cattr ./.helm -n cattr -f my-values.yaml --wait --timeout 15m
helm rollback cattr <revision> -n cattr --wait --timeout 15m   # schema stays migrated
helm uninstall cattr -n cattr   # keeps Secret <fullname>, PVC <fullname>-storage, PVC <fullname>-mysql
```

Use a values file rather than `--reuse-values`; `values.schema.json` rejects
unknown keys (including chart 0.1.x keys). Reinstalling with the same release
name reuses the kept Secret and PVCs. Delete them explicitly to drop the data.

## Values

See [values.yaml](values.yaml) for every value with its description; the
schema is in [values.schema.json](values.schema.json). Main groups:

| Key | Default | Description |
|---|---|---|
| `image.registry` / `repository` / `tag` / `digest` | `ghcr.io` / `cattr-app/server` / appVersion / `""` | application image |
| `cattr.appUrl` | `""` (required) | public URL |
| `cattr.appKey`, `cattr.reverbAppSecret`, `cattr.existingSecret` | `""` | see Secrets |
| `cattr.restartPolicy` | `shutdown` | `CATTR_RESTART_POLICY` |
| `cattr.extraEnv`, `cattr.extraEnvFrom` | `[]` | env for every Cattr container (e.g. mail) |
| `storage.type` | `pvc` | `pvc` or `s3` |
| `storage.pvc.*` | 20Gi RWO | `existingClaim`, `storageClass`, `size`, `accessModes`, `annotations` |
| `storage.s3.*` | | `bucket`, `region`, `endpoint`, `url`, `usePathStyleEndpoint`, credentials |
| `storage.runtime.sizeLimit` | `2Gi` | per-container emptyDir |
| `redis.*` | disabled | external Redis |
| `database.*` | | `host` (required), `port`, `name`, credentials, `existingSecret` |
| `mysql.enabled` | `false` | demo MySQL |
| `setup.*` | hook | `hook`, `activeDeadlineSeconds`, `backoffLimit`, `resources`, `admin.*` |
| `web.*` | 1 replica | `octaneWorkers`/`octaneTaskWorkers` (2/2), resources, probes, `preStopSleepSeconds`, `extraEnv`, `pdb` |
| `queue.*` | 1 replica | resources, liveness, `extraEnv`, `pdb` |
| `scheduler.*` | | resources, liveness, `extraEnv` |
| `podSecurityContext`, `containerSecurityContext` | restricted | |
| `terminationGracePeriodSeconds` | `45` | |
| `nodeSelector`, `tolerations`, `affinity`, `topologySpreadConstraints`, `priorityClassName` | | scheduling of Cattr Pods |
| `service.*`, `ingress.*`, `httpRoute.*`, `networkPolicy.*` | | see Networking |

## Development

`sh .helm/ci/check.sh` (needs `helm` 3.14+/4 and `kubeconform`, e.g. in the
`alpine/k8s` image) lints and renders every `ci/*-values.yaml`, checks that
renders are deterministic, validates with kubeconform for Kubernetes 1.29 and
1.34 and checks the renders that must fail. CI runs it in the "Helm chart"
PR check.
