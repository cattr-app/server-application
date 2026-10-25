{{/*
Pod building blocks shared by the pvc-mode Deployment, the s3-mode Deployments
and the setup Job.

Filesystem layout (readOnlyRootFilesystem):
- every container gets its own emptyDir "<role>-runtime" mounted with subPaths:
    pebble          -> /opt/cattr/.pebble          (Pebble state, socket, layers)
    bootstrap-cache -> /opt/cattr/app/bootstrap/cache
    framework       -> /opt/cattr/app/storage/framework (sessions, views)
    logs            -> /opt/cattr/app/storage/logs
    tmp             -> /tmp
  The "prepare" init container seeds pebble and bootstrap-cache from the image.
- the shared application data (cattr.dataPaths) comes from the PVC in pvc
  mode, and from the node-local runtime emptyDir in s3 mode.
*/}}

{{/*
Application data paths below /opt/cattr/app that must be writable.
storage/framework/cache is shared through the PVC in pvc mode (file cache and
locks); in s3 mode it stays inside the framework emptyDir (Redis holds cache).
*/}}
{{- define "cattr.dataPaths" -}}
{{- $paths := list "storage/app/screenshots" "storage/app/attachments" "storage/app/tmp" "storage/app/public/reports" -}}
{{- if ne .Values.storage.type "s3" -}}
{{- $paths = append $paths "storage/framework/cache" -}}
{{- end -}}
{{- join " " $paths -}}
{{- end }}

{{- define "cattr.pvcClaimName" -}}
{{- .Values.storage.pvc.existingClaim | default (printf "%s-storage" (include "cattr.fullname" .)) }}
{{- end }}

{{/*
Environment of a Cattr container.
Usage: include "cattr.env" (dict "context" $ "role" "web" "chartSecret" $name)
*/}}
{{- define "cattr.env" -}}
{{- $ctx := .context -}}
{{- $v := $ctx.Values -}}
{{- $role := .role -}}
{{- $sec := dict "context" $ctx "chartSecret" .chartSecret -}}
- name: APP_ENV
  value: {{ $v.cattr.appEnv | quote }}
- name: APP_DEBUG
  value: {{ $v.cattr.appDebug | toString | quote }}
- name: APP_URL
  value: {{ $v.cattr.appUrl | quote }}
- name: LOG_CHANNEL
  value: {{ $v.cattr.logChannel | quote }}
- name: CATTR_RESTART_POLICY
  value: {{ $v.cattr.restartPolicy | quote }}
- name: DB_CONNECTION
  value: mysql
- name: DB_HOST
  value: {{ include "cattr.mysql.host" $ctx | quote }}
- name: DB_PORT
  value: {{ $v.database.port | toString | quote }}
- name: DB_DATABASE
  value: {{ $v.database.name | quote }}
{{- range $var := list "DB_USERNAME" "DB_PASSWORD" "APP_KEY" "REVERB_APP_SECRET" }}
{{ include "cattr.secretEnv" (merge (dict "var" $var) $sec) }}
{{- end }}
{{- if $v.redis.enabled }}
- name: CACHE_STORE
  value: redis
- name: CACHE_DRIVER
  value: redis
- name: REDIS_HOST
  value: {{ $v.redis.host | quote }}
- name: REDIS_PORT
  value: {{ $v.redis.port | toString | quote }}
- name: REDIS_DB
  value: {{ $v.redis.database | toString | quote }}
- name: REDIS_CACHE_DB
  value: {{ $v.redis.cacheDatabase | toString | quote }}
{{- with $v.redis.username }}
- name: REDIS_USERNAME
  value: {{ . | quote }}
{{- end }}
{{- if or $v.redis.password $v.redis.existingSecret }}
{{ include "cattr.secretEnv" (merge (dict "var" "REDIS_PASSWORD") $sec) }}
{{- end }}
{{- end }}
{{- if eq $v.storage.type "s3" }}
- name: FILESYSTEM_DISK
  value: s3
- name: AWS_BUCKET
  value: {{ $v.storage.s3.bucket | quote }}
- name: AWS_DEFAULT_REGION
  value: {{ $v.storage.s3.region | quote }}
{{- with $v.storage.s3.endpoint }}
- name: AWS_ENDPOINT
  value: {{ . | quote }}
{{- end }}
{{- with $v.storage.s3.url }}
- name: AWS_URL
  value: {{ . | quote }}
{{- end }}
- name: AWS_USE_PATH_STYLE_ENDPOINT
  value: {{ $v.storage.s3.usePathStyleEndpoint | toString | quote }}
{{- if or $v.storage.s3.accessKeyId $v.storage.s3.existingSecret }}
{{ include "cattr.secretEnv" (merge (dict "var" "AWS_ACCESS_KEY_ID") $sec) }}
{{ include "cattr.secretEnv" (merge (dict "var" "AWS_SECRET_ACCESS_KEY") $sec) }}
{{- end }}
{{- if has $role (list "queue" "scheduler" "setup") }}
{{- /* Reverb runs in the web Pods; other Pods broadcast through the Service. */}}
- name: REVERB_HOST
  value: {{ include "cattr.reverbServiceName" $ctx | quote }}
- name: REVERB_PORT
  value: "8081"
- name: REVERB_SCHEME
  value: http
{{- end }}
{{- end }}
{{- if eq $role "web" }}
{{- with $v.web.octaneWorkers }}
- name: OCTANE_WORKERS
  value: {{ . | toString | quote }}
{{- end }}
{{- with $v.web.octaneTaskWorkers }}
- name: OCTANE_TASK_WORKERS
  value: {{ . | toString | quote }}
{{- end }}
{{- end }}
{{- if eq $role "setup" }}
{{- if include "cattr.setup.provision" $ctx }}
- name: APP_ADMIN_EMAIL
  value: {{ $v.setup.admin.email | quote }}
- name: APP_ADMIN_NAME
  value: {{ $v.setup.admin.name | quote }}
{{ include "cattr.secretEnv" (merge (dict "var" "APP_ADMIN_PASSWORD") $sec) }}
{{- end }}
{{- end }}
{{- with $v.cattr.extraEnv }}
{{ toYaml . }}
{{- end }}
{{- with (get (get $v $role | default dict) "extraEnv") }}
{{ toYaml . }}
{{- end }}
{{- end }}

{{/*
volumeMounts of a Cattr container (role web/queue/scheduler/setup).
*/}}
{{- define "cattr.volumeMounts" -}}
{{- $ctx := .context -}}
{{- $rt := printf "%s-runtime" .role -}}
- name: {{ $rt }}
  mountPath: /opt/cattr/.pebble
  subPath: pebble
- name: {{ $rt }}
  mountPath: /opt/cattr/app/bootstrap/cache
  subPath: bootstrap-cache
- name: {{ $rt }}
  mountPath: /opt/cattr/app/storage/framework
  subPath: framework
- name: {{ $rt }}
  mountPath: /opt/cattr/app/storage/logs
  subPath: logs
- name: {{ $rt }}
  mountPath: /tmp
  subPath: tmp
{{- if ne .role "setup" }}
{{- range $p := splitList " " (include "cattr.dataPaths" $ctx) }}
{{- if eq $ctx.Values.storage.type "s3" }}
- name: {{ $rt }}
  mountPath: /opt/cattr/app/{{ $p }}
  subPath: data/{{ $p }}
{{- else }}
- name: storage
  mountPath: /opt/cattr/app/{{ $p }}
  subPath: {{ $p }}
{{- end }}
{{- end }}
{{- with $ctx.Values.extraVolumeMounts }}
{{ toYaml . }}
{{- end }}
{{- end }}
{{- end }}

{{/*
envFrom of a Cattr container.
*/}}
{{- define "cattr.envFrom" -}}
{{- with .Values.cattr.extraEnvFrom }}
envFrom:
  {{- toYaml . | nindent 2 }}
{{- end }}
{{- end }}

{{/*
"prepare" init container: seeds the per-container emptyDirs from the image
and creates the data directories on the PVC root (so kubelet does not create
the subPath directories as root). Bypasses the entrypoint on purpose.
Usage: include "cattr.prepareContainer" (dict "context" $ "roles" (list "web" "queue"))
*/}}
{{- define "cattr.prepareContainer" -}}
{{- $ctx := .context -}}
{{- $pvc := and (ne $ctx.Values.storage.type "s3") (not (has "setup" .roles)) -}}
{{- $paths := include "cattr.dataPaths" $ctx -}}
- name: prepare
  image: {{ include "cattr.image" $ctx }}
  imagePullPolicy: {{ $ctx.Values.image.pullPolicy }}
  command:
    - /bin/sh
    - -c
  args:
    - |
      set -eu
      for dir in /runtime/*; do
        mkdir -p "$dir/pebble" "$dir/bootstrap-cache" "$dir/logs" "$dir/tmp" \
          "$dir/framework/cache" "$dir/framework/sessions" "$dir/framework/views"
        cp -a /opt/cattr/.pebble/. "$dir/pebble/"
        cp -a /opt/cattr/app/bootstrap/cache/. "$dir/bootstrap-cache/"
        for path in {{ $paths }}; do mkdir -p "$dir/data/$path"; done
      done
      {{- if $pvc }}
      for path in {{ $paths }}; do mkdir -p "/data/$path"; done
      {{- end }}
      echo "prepare: runtime directories ready"
  {{- with $ctx.Values.containerSecurityContext }}
  securityContext:
    {{- toYaml . | nindent 4 }}
  {{- end }}
  resources:
    {{- toYaml $ctx.Values.initResources | nindent 4 }}
  volumeMounts:
    {{- range $r := .roles }}
    - name: {{ $r }}-runtime
      mountPath: /runtime/{{ $r }}
    {{- end }}
    {{- if $pvc }}
    - name: storage
      mountPath: /data
    {{- end }}
{{- end }}

{{/*
"wait-for-migrations" init container: blocks until the database schema
matches the migrations shipped in this image (the setup Job ran), so new
application code never starts against an old schema.
`migrate:status --pending=1` exits 0 only when the migrations table exists and
nothing is pending; it exits 1 when migrations are pending, the table is
missing or the database is unreachable.
*/}}
{{- define "cattr.waitContainer" -}}
{{- $ctx := .context -}}
- name: wait-for-migrations
  image: {{ include "cattr.image" $ctx }}
  imagePullPolicy: {{ $ctx.Values.image.pullPolicy }}
  workingDir: /opt/cattr/app
  command:
    - /bin/sh
    - -c
  args:
    - |
      until out="$(php artisan migrate:status --pending=1 --no-interaction 2>&1)"; do
        echo "wait-for-migrations: schema not ready, retrying in 5s: $(printf '%s' "$out" | tr -s '\n' ' ' | tail -c 300)"
        sleep 5
      done
      echo "wait-for-migrations: no pending migrations"
  env:
    {{- include "cattr.env" (dict "context" $ctx "role" .role "chartSecret" (include "cattr.secretName" $ctx)) | trim | nindent 4 }}
  {{- with (include "cattr.envFrom" $ctx) }}
  {{- . | nindent 2 }}
  {{- end }}
  {{- with $ctx.Values.containerSecurityContext }}
  securityContext:
    {{- toYaml . | nindent 4 }}
  {{- end }}
  resources:
    {{- toYaml $ctx.Values.initResources | nindent 4 }}
  volumeMounts:
    - name: {{ .role }}-runtime
      mountPath: /tmp
      subPath: tmp
{{- end }}

{{/*
"wait-for-database" init container of the setup Job: retries until the
database accepts a login, so a database that is still starting (e.g. the demo
MySQL created in the same install) does not fail the migration. Bounded by
setup.activeDeadlineSeconds.
*/}}
{{- define "cattr.waitDatabaseContainer" -}}
{{- $ctx := .context -}}
- name: wait-for-database
  image: {{ include "cattr.image" $ctx }}
  imagePullPolicy: {{ $ctx.Values.image.pullPolicy }}
  command:
    - /bin/sh
    - -c
  args:
    - |
      until err="$(php -r 'try { new PDO("mysql:host=" . getenv("DB_HOST") . ";port=" . getenv("DB_PORT"), getenv("DB_USERNAME"), getenv("DB_PASSWORD"), [PDO::ATTR_TIMEOUT => 5]); } catch (Throwable $e) { fwrite(STDERR, $e->getMessage()); exit(1); }' 2>&1)"; do
        echo "wait-for-database: ${DB_HOST}:${DB_PORT} not ready, retrying in 5s: ${err}"
        sleep 5
      done
      echo "wait-for-database: ${DB_HOST}:${DB_PORT} accepts connections"
  env:
    {{- include "cattr.env" (dict "context" $ctx "role" "setup" "chartSecret" .chartSecret) | trim | nindent 4 }}
  {{- with $ctx.Values.containerSecurityContext }}
  securityContext:
    {{- toYaml . | nindent 4 }}
  {{- end }}
  resources:
    {{- toYaml $ctx.Values.initResources | nindent 4 }}
  volumeMounts:
    - name: setup-runtime
      mountPath: /tmp
      subPath: tmp
{{- end }}

{{/*
Long-running application container.
Usage: include "cattr.appContainer" (dict "context" $ "role" "web")
*/}}
{{- define "cattr.appContainer" -}}
{{- $ctx := .context -}}
{{- $v := $ctx.Values -}}
{{- $role := .role -}}
{{- $cfg := get $v $role -}}
- name: {{ $role }}
  image: {{ include "cattr.image" $ctx }}
  imagePullPolicy: {{ $v.image.pullPolicy }}
  args:
    - {{ $role }}
  env:
    {{- include "cattr.env" (dict "context" $ctx "role" $role "chartSecret" (include "cattr.secretName" $ctx)) | trim | nindent 4 }}
  {{- with (include "cattr.envFrom" $ctx) }}
  {{- . | nindent 2 }}
  {{- end }}
  {{- if eq $role "web" }}
  ports:
    - name: http
      containerPort: 8080
      protocol: TCP
    - name: reverb
      containerPort: 8081
      protocol: TCP
  {{- with $cfg.startupProbe }}
  {{- if .enabled }}
  startupProbe:
    httpGet:
      path: /actuator/health/liveness
      port: http
    periodSeconds: {{ .periodSeconds }}
    timeoutSeconds: {{ .timeoutSeconds }}
    failureThreshold: {{ .failureThreshold }}
  {{- end }}
  {{- end }}
  {{- with $cfg.readinessProbe }}
  {{- if .enabled }}
  readinessProbe:
    httpGet:
      path: /api/status
      port: http
    periodSeconds: {{ .periodSeconds }}
    timeoutSeconds: {{ .timeoutSeconds }}
    failureThreshold: {{ .failureThreshold }}
  {{- end }}
  {{- end }}
  {{- with $cfg.livenessProbe }}
  {{- if .enabled }}
  livenessProbe:
    httpGet:
      path: /actuator/health/liveness
      port: http
    periodSeconds: {{ .periodSeconds }}
    timeoutSeconds: {{ .timeoutSeconds }}
    failureThreshold: {{ .failureThreshold }}
  {{- end }}
  {{- end }}
  {{- if $cfg.preStopSleepSeconds }}
  lifecycle:
    preStop:
      exec:
        command:
          - sleep
          - {{ $cfg.preStopSleepSeconds | toString | quote }}
  {{- end }}
  {{- else }}
  {{- with $cfg.livenessProbe }}
  {{- if .enabled }}
  livenessProbe:
    exec:
      command:
        - /bin/sh
        - -c
        - pebble services {{ $role }} | grep -qw active
    initialDelaySeconds: {{ .initialDelaySeconds }}
    periodSeconds: {{ .periodSeconds }}
    timeoutSeconds: {{ .timeoutSeconds }}
    failureThreshold: {{ .failureThreshold }}
  {{- end }}
  {{- end }}
  {{- end }}
  {{- with $v.containerSecurityContext }}
  securityContext:
    {{- toYaml . | nindent 4 }}
  {{- end }}
  resources:
    {{- toYaml $cfg.resources | nindent 4 }}
  volumeMounts:
    {{- include "cattr.volumeMounts" (dict "context" $ctx "role" $role) | trim | nindent 4 }}
{{- end }}

{{/*
Volumes of a Pod.
Usage: include "cattr.volumes" (dict "context" $ "roles" (list ...))
*/}}
{{- define "cattr.volumes" -}}
{{- $ctx := .context -}}
{{- range $r := .roles }}
- name: {{ $r }}-runtime
  emptyDir:
    {{- with $ctx.Values.storage.runtime.medium }}
    medium: {{ . }}
    {{- end }}
    {{- with $ctx.Values.storage.runtime.sizeLimit }}
    sizeLimit: {{ . }}
    {{- end }}
{{- end }}
{{- if and (ne $ctx.Values.storage.type "s3") (not (has "setup" .roles)) }}
- name: storage
  persistentVolumeClaim:
    claimName: {{ include "cattr.pvcClaimName" $ctx }}
{{- end }}
{{- if not (has "setup" .roles) }}
{{- with $ctx.Values.extraVolumes }}
{{ toYaml . }}
{{- end }}
{{- end }}
{{- end }}

{{/*
Pod-level settings shared by every Cattr Pod (everything except containers and volumes).
*/}}
{{- define "cattr.podCommon" -}}
{{- $v := .Values -}}
{{- with (include "cattr.imagePullSecrets" .) }}
{{- . }}
{{- end }}
serviceAccountName: {{ include "cattr.serviceAccountName" . }}
automountServiceAccountToken: {{ $v.serviceAccount.automountServiceAccountToken }}
enableServiceLinks: false
terminationGracePeriodSeconds: {{ $v.terminationGracePeriodSeconds }}
{{- with $v.podSecurityContext }}
securityContext:
  {{- toYaml . | nindent 2 }}
{{- end }}
{{- with $v.priorityClassName }}
priorityClassName: {{ . }}
{{- end }}
{{- with $v.nodeSelector }}
nodeSelector:
  {{- toYaml . | nindent 2 }}
{{- end }}
{{- with $v.tolerations }}
tolerations:
  {{- toYaml . | nindent 2 }}
{{- end }}
{{- with $v.affinity }}
affinity:
  {{- toYaml . | nindent 2 }}
{{- end }}
{{- end }}

{{/*
Pod template metadata annotations.
Usage: include "cattr.podAnnotations" (dict "context" $ "extra" .Values.web.podAnnotations)
*/}}
{{- define "cattr.podAnnotations" -}}
{{- $ctx := .context -}}
{{- $checksum := include "cattr.secretChecksum" $ctx -}}
checksum/secret: {{ $checksum | quote }}
{{- with $ctx.Values.podAnnotations }}
{{ toYaml . }}
{{- end }}
{{- with .extra }}
{{ toYaml . }}
{{- end }}
{{- end }}

{{/*
Full Pod template of an application workload.
Usage: include "cattr.podTemplate" (dict "context" $ "roles" (list "web") "selector" $selectorYaml "extraAnnotations" $map)
*/}}
{{- define "cattr.podTemplate" -}}
{{- $ctx := .context -}}
metadata:
  labels:
    {{- .selector | nindent 4 }}
    app.kubernetes.io/part-of: cattr
    {{- with $ctx.Values.podLabels }}
    {{- toYaml . | nindent 4 }}
    {{- end }}
  annotations:
    {{- include "cattr.podAnnotations" (dict "context" $ctx "extra" .extraAnnotations) | nindent 4 }}
spec:
  {{- include "cattr.podCommon" $ctx | trim | nindent 2 }}
  {{- if and (eq $ctx.Values.storage.type "s3") $ctx.Values.topologySpreadConstraints }}
  topologySpreadConstraints:
    {{- toYaml $ctx.Values.topologySpreadConstraints | nindent 4 }}
  {{- end }}
  initContainers:
    {{- include "cattr.prepareContainer" (dict "context" $ctx "roles" .roles) | trim | nindent 4 }}
    {{- include "cattr.waitContainer" (dict "context" $ctx "role" (first .roles)) | trim | nindent 4 }}
  containers:
    {{- range $r := .roles }}
    {{- include "cattr.appContainer" (dict "context" $ctx "role" $r) | trim | nindent 4 }}
    {{- end }}
  volumes:
    {{- include "cattr.volumes" (dict "context" $ctx "roles" .roles) | trim | nindent 4 }}
{{- end }}
