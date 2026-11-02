{{/*
Non-empty when the setup Job provisions an administrator: admin enabled and
both email and password (value or existingSecret) available.
*/}}
{{- define "cattr.setup.provision" -}}
{{- $a := .Values.setup.admin -}}
{{- if and $a.enabled $a.email (or $a.password $a.existingSecret) -}}
true
{{- end -}}
{{- end }}

{{/*
Reject ambiguous or duplicate env declarations. Built-in variables cannot be
redeclared through extension points; otherwise Kubernetes accepts duplicates
with surprising precedence. Module identifiers are normalized as PHP/JS do.
*/}}
{{- define "cattr.validateEnv" -}}
{{- $v := .Values -}}
{{- $used := dict -}}
{{- range $name := list "APP_ENV" "APP_DEBUG" "APP_URL" "LOG_CHANNEL" "CATTR_RESTART_POLICY" "DB_CONNECTION" "DB_HOST" "DB_PORT" "DB_DATABASE" "DB_USERNAME" "DB_PASSWORD" "APP_KEY" "REVERB_APP_SECRET" "CACHE_STORE" "CACHE_DRIVER" "REDIS_HOST" "REDIS_PORT" "REDIS_DB" "REDIS_CACHE_DB" "REDIS_USERNAME" "REDIS_PASSWORD" "FILESYSTEM_DISK" "AWS_BUCKET" "AWS_DEFAULT_REGION" "AWS_ENDPOINT" "AWS_URL" "AWS_USE_PATH_STYLE_ENDPOINT" "AWS_ACCESS_KEY_ID" "AWS_SECRET_ACCESS_KEY" "REVERB_HOST" "REVERB_PORT" "REVERB_SCHEME" "OCTANE_WORKERS" "OCTANE_TASK_WORKERS" "APP_ADMIN_EMAIL" "APP_ADMIN_NAME" "APP_ADMIN_PASSWORD" -}}
{{- $_ := set $used $name "built-in" -}}
{{- end -}}
{{- range $side := list "backend" "frontend" -}}
{{- range $id := keys (get $v.modules $side | default dict) | sortAlpha -}}
{{- $suffix := include "cattr.moduleFlagName" $id -}}
{{- if not (regexMatch "^[A-Z][A-Z0-9_]*$" $suffix) -}}
{{- fail (printf "modules.%s identifier %q normalizes to invalid flag %q; use a module identifier starting with a letter" $side $id $suffix) -}}
{{- end -}}
{{- $flag := printf "MODULE_%s_%s_ENABLED" (upper $side) $suffix -}}
{{- if hasKey $used $flag -}}
{{- fail (printf "modules.%s identifier %q generates duplicate environment variable %s" $side $id $flag) -}}
{{- end -}}
{{- $_ := set $used $flag (printf "modules.%s" $side) -}}
{{- end -}}
{{- end -}}
{{- range $name := keys $v.cattr.env | sortAlpha -}}
{{- if hasKey $used $name -}}
{{- fail (printf "cattr.env.%s duplicates an existing environment variable (%s)" $name (get $used $name)) -}}
{{- end -}}
{{- $_ := set $used $name "cattr.env" -}}
{{- end -}}
{{- range $item := $v.cattr.extraEnv -}}
{{- $name := $item.name | default "" -}}
{{- if not (regexMatch "^[A-Za-z_][A-Za-z0-9_]*$" $name) -}}
{{- fail (printf "cattr.extraEnv contains invalid environment variable name %q" $name) -}}
{{- end -}}
{{- if hasKey $used $name -}}
{{- fail (printf "cattr.extraEnv %s duplicates an existing environment variable (%s)" $name (get $used $name)) -}}
{{- end -}}
{{- $_ := set $used $name "cattr.extraEnv" -}}
{{- end -}}
{{- range $role := list "web" "queue" "scheduler" "setup" -}}
{{- $roleNames := deepCopy $used -}}
{{- range $item := get (get $v $role) "extraEnv" | default list -}}
{{- $name := $item.name | default "" -}}
{{- if not (regexMatch "^[A-Za-z_][A-Za-z0-9_]*$" $name) -}}
{{- fail (printf "%s.extraEnv contains invalid environment variable name %q" $role $name) -}}
{{- end -}}
{{- if hasKey $roleNames $name -}}
{{- fail (printf "%s.extraEnv %s duplicates an existing environment variable (%s)" $role $name (get $roleNames $name)) -}}
{{- end -}}
{{- $_ := set $roleNames $name (printf "%s.extraEnv" $role) -}}
{{- end -}}
{{- end -}}
{{- end }}

{{/*
Render-time validation. Every message names the value to fix.
*/}}
{{- define "cattr.validate" -}}
{{- $v := .Values -}}
{{- $fullname := include "cattr.fullname" . -}}

{{- if not $v.cattr.appUrl -}}
{{- fail "cattr.appUrl is required: the public URL of the instance, e.g. https://cattr.example.com" -}}
{{- end -}}
{{- if not (regexMatch "^https?://[^/]+" $v.cattr.appUrl) -}}
{{- fail (printf "cattr.appUrl must be an absolute http(s) URL, got %q" $v.cattr.appUrl) -}}
{{- end -}}

{{- if not (has $v.storage.type (list "pvc" "s3")) -}}
{{- fail (printf "storage.type must be pvc or s3, got %q" $v.storage.type) -}}
{{- end -}}

{{- if not (has $v.cattr.restartPolicy (list "shutdown" "restart")) -}}
{{- fail (printf "cattr.restartPolicy must be shutdown or restart, got %q" $v.cattr.restartPolicy) -}}
{{- end -}}

{{- /* Database */ -}}
{{- if and (not $v.database.host) (not $v.mysql.enabled) -}}
{{- fail "database.host is required: point it to an external MySQL/MariaDB (or set mysql.enabled=true for the single-node demo database)" -}}
{{- end -}}
{{- if and $v.database.host $v.mysql.enabled -}}
{{- fail "database.host and mysql.enabled are mutually exclusive: leave database.host empty to use the built-in MySQL" -}}
{{- end -}}
{{- if and (not $v.mysql.enabled) (not $v.mysql.acknowledgeRemoval) -}}
{{- $old := lookup "apps/v1" "Deployment" .Release.Namespace (include "cattr.mysql.fullname" .) -}}
{{- if $old -}}
{{- fail (printf "the release still runs the built-in MySQL Deployment %s/%s but mysql.enabled=false (the default since chart 0.2.0). Set mysql.enabled=true to keep it, or migrate the data to an external database and set mysql.acknowledgeRemoval=true. See MIGRATION-0.1-to-0.2.md" .Release.Namespace (include "cattr.mysql.fullname" .)) -}}
{{- end -}}
{{- end -}}

{{- /* Redis */ -}}
{{- if and $v.redis.enabled (not $v.redis.host) -}}
{{- fail "redis.host is required when redis.enabled=true" -}}
{{- end -}}

{{- /* Storage modes and replicas */ -}}
{{- if eq $v.storage.type "s3" -}}
{{- if not $v.redis.enabled -}}
{{- fail "storage.type=s3 requires an external Redis for cache and locks: set redis.enabled=true and redis.host" -}}
{{- end -}}
{{- if not $v.storage.s3.bucket -}}
{{- fail "storage.s3.bucket is required when storage.type=s3" -}}
{{- end -}}
{{- if and (not $v.storage.s3.existingSecret) (ne (empty $v.storage.s3.accessKeyId) (empty $v.storage.s3.secretAccessKey)) -}}
{{- fail "storage.s3.accessKeyId and storage.s3.secretAccessKey must be set together (or use storage.s3.existingSecret; leave both empty for ambient credentials)" -}}
{{- end -}}
{{- if and (gt (int $v.web.replicas) 1) (not $v.web.unsupportedMultiReplica) -}}
{{- fail "web.replicas > 1 is not supported: settings are cached per Pod by Octane and Reverb broadcasts would reach only one Pod. Set web.unsupportedMultiReplica=true to override (see README, Scaling)" -}}
{{- end -}}
{{- else -}}
{{- if or (gt (int $v.web.replicas) 1) (gt (int $v.queue.replicas) 1) -}}
{{- fail "storage.type=pvc runs web, queue and scheduler in one Pod on a ReadWriteOnce volume: web.replicas and queue.replicas must be 1. Use storage.type=s3 to scale" -}}
{{- end -}}
{{- end -}}
{{- if lt (int $v.web.replicas) 1 -}}
{{- fail "web.replicas must be at least 1" -}}
{{- end -}}

{{- /* Setup / administrator */ -}}
{{- $admin := $v.setup.admin -}}
{{- if and $admin.enabled .Release.IsInstall (not (include "cattr.setup.provision" .)) -}}
{{- fail "setup.admin.enabled=true needs setup.admin.email and setup.admin.password (or setup.admin.existingSecret) on a fresh install; set setup.admin.enabled=false to skip creating an administrator" -}}
{{- end -}}
{{- if and $admin.password (lt (len ($admin.password | toString)) 6) -}}
{{- fail "setup.admin.password must be at least 6 characters" -}}
{{- end -}}

{{- /* Ingress */ -}}
{{- if and $v.ingress.enabled (not $v.ingress.hosts) -}}
{{- fail "ingress.hosts must contain at least one host when ingress.enabled=true" -}}
{{- end -}}

{{- /* Gateway API */ -}}
{{- if and $v.httpRoute.enabled (not (.Capabilities.APIVersions.Has "gateway.networking.k8s.io/v1/HTTPRoute")) -}}
{{- fail "httpRoute.enabled=true but the cluster does not serve gateway.networking.k8s.io/v1 HTTPRoute: install the Gateway API CRDs (for `helm template` pass --api-versions gateway.networking.k8s.io/v1/HTTPRoute)" -}}
{{- end -}}
{{- if and $v.httpRoute.enabled (not $v.httpRoute.parentRefs) -}}
{{- fail "httpRoute.parentRefs is required when httpRoute.enabled=true" -}}
{{- end -}}

{{- /* Validate env overrides, module names, and duplicates. */ -}}
{{- $_ := include "cattr.validateEnv" . -}}

{{- /* Resolve every chart-managed secret once (fails on missing/invalid values). */ -}}
{{- $_ := include "cattr.secretData" . -}}
{{- end }}
