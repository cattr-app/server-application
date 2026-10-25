{{/*
Resolve one entry of the chart Secret's data map, already base64-encoded.
Order: explicit value from values -> value stored in the existing chart Secret
(kept byte-for-byte, so upgrades and rollbacks never rotate it) -> fail.
No value is ever generated randomly.
Usage: include "cattr.secretValue" (dict "context" $ "key" "APP_KEY" "value" $plain "hint" "set cattr.appKey ...")
*/}}
{{- define "cattr.secretValue" -}}
{{- $ctx := .context -}}
{{- $secretName := include "cattr.secretName" $ctx -}}
{{- if .value -}}
{{- .value | toString | b64enc -}}
{{- else -}}
{{- $existing := lookup "v1" "Secret" $ctx.Release.Namespace $secretName -}}
{{- $stored := "" -}}
{{- if $existing -}}
{{- $stored = index ($existing.data | default dict) .key | default "" -}}
{{- end -}}
{{- if $stored -}}
{{- $stored -}}
{{- else -}}
{{- fail (printf "%s is required: %s (not set in values and not found in the existing Secret %s/%s)" .key .hint $ctx.Release.Namespace $secretName) -}}
{{- end -}}
{{- end -}}
{{- end }}

{{/*
APP_KEY Secret entry. cattr.appKey accepts a raw base64 key or a Laravel
"base64:..." key; the "base64:" prefix is added only when missing. The key
must decode to 32 bytes (AES-256-CBC, config/app.php cipher).
*/}}
{{- define "cattr.secret.appKey" -}}
{{- $appKey := .Values.cattr.appKey | default "" | toString -}}
{{- if and $appKey (not (hasPrefix "base64:" $appKey)) -}}
{{- $appKey = printf "base64:%s" $appKey -}}
{{- end -}}
{{- $encoded := include "cattr.secretValue" (dict "context" . "key" "APP_KEY" "value" $appKey "hint" "set cattr.appKey to a Laravel application key, e.g. the output of `php artisan key:generate --show`, or use cattr.existingSecret") -}}
{{- $raw := $encoded | b64dec | trimPrefix "base64:" -}}
{{- if ne (len ($raw | b64dec)) 32 -}}
{{- fail "APP_KEY must be a base64-encoded 32-byte key (`php artisan key:generate --show`, or `printf 'base64:%s' \"$(openssl rand -base64 32)\"`)" -}}
{{- end -}}
{{- $encoded -}}
{{- end }}

{{/*
REVERB_APP_SECRET Secret entry.
*/}}
{{- define "cattr.secret.reverbAppSecret" -}}
{{- include "cattr.secretValue" (dict "context" . "key" "REVERB_APP_SECRET" "value" (.Values.cattr.reverbAppSecret | default "") "hint" "set cattr.reverbAppSecret to a random secret, e.g. the output of `openssl rand -hex 32`, or use cattr.existingSecret") -}}
{{- end }}

{{/*
Data of the chart-managed Secret as YAML (key: base64 value), sorted.
Only keys not supplied through an existingSecret are included. Rendered by
secret.yaml and setup-secret.yaml and hashed into the checksum/secret Pod
annotation, so it must stay deterministic.
*/}}
{{- define "cattr.secretData" -}}
{{- $data := dict -}}
{{- if not .Values.cattr.existingSecret -}}
{{- $_ := set $data "APP_KEY" (include "cattr.secret.appKey" .) -}}
{{- $_ := set $data "REVERB_APP_SECRET" (include "cattr.secret.reverbAppSecret" .) -}}
{{- end -}}
{{- if not .Values.database.existingSecret -}}
{{- $_ := set $data "DB_USERNAME" (include "cattr.secretValue" (dict "context" . "key" "DB_USERNAME" "value" (.Values.database.username | default "") "hint" "set database.username or database.existingSecret")) -}}
{{- $_ := set $data "DB_PASSWORD" (include "cattr.secretValue" (dict "context" . "key" "DB_PASSWORD" "value" (.Values.database.password | default "") "hint" "set database.password or database.existingSecret")) -}}
{{- if .Values.mysql.enabled -}}
{{- $_ := set $data "MYSQL_ROOT_PASSWORD" (include "cattr.secretValue" (dict "context" . "key" "MYSQL_ROOT_PASSWORD" "value" (.Values.database.rootPassword | default "") "hint" "set database.rootPassword (built-in MySQL) or database.existingSecret")) -}}
{{- end -}}
{{- end -}}
{{- if and .Values.redis.enabled .Values.redis.password (not .Values.redis.existingSecret) -}}
{{- $_ := set $data "REDIS_PASSWORD" (.Values.redis.password | toString | b64enc) -}}
{{- end -}}
{{- if and (eq .Values.storage.type "s3") .Values.storage.s3.accessKeyId (not .Values.storage.s3.existingSecret) -}}
{{- $_ := set $data "AWS_ACCESS_KEY_ID" (.Values.storage.s3.accessKeyId | toString | b64enc) -}}
{{- $_ := set $data "AWS_SECRET_ACCESS_KEY" (.Values.storage.s3.secretAccessKey | toString | b64enc) -}}
{{- end -}}
{{- range $k := keys $data | sortAlpha }}
{{ $k }}: {{ index $data $k | quote }}
{{- end }}
{{- end }}

{{/*
checksum/secret annotation value: sha256 of the resolved chart-managed Secret data.
*/}}
{{- define "cattr.secretChecksum" -}}
{{- include "cattr.secretData" . | sha256sum -}}
{{- end }}

{{/*
Where a secret-backed variable comes from: {name, key} for secretKeyRef.
Usage: include "cattr.secretRef" (dict "context" $ "var" "APP_KEY" "chartSecret" $name) | fromYaml
*/}}
{{- define "cattr.secretRef" -}}
{{- $v := .context.Values -}}
{{- $var := .var -}}
{{- $name := .chartSecret -}}
{{- $key := $var -}}
{{- if and (has $var (list "APP_KEY" "REVERB_APP_SECRET")) $v.cattr.existingSecret -}}
{{- $name = $v.cattr.existingSecret -}}
{{- $key = ternary $v.cattr.existingSecretKeys.appKey $v.cattr.existingSecretKeys.reverbAppSecret (eq $var "APP_KEY") -}}
{{- else if and (has $var (list "DB_USERNAME" "DB_PASSWORD" "MYSQL_ROOT_PASSWORD")) $v.database.existingSecret -}}
{{- $name = $v.database.existingSecret -}}
{{- $key = get (dict "DB_USERNAME" $v.database.existingSecretKeys.username "DB_PASSWORD" $v.database.existingSecretKeys.password "MYSQL_ROOT_PASSWORD" $v.database.existingSecretKeys.rootPassword) $var -}}
{{- else if and (eq $var "REDIS_PASSWORD") $v.redis.existingSecret -}}
{{- $name = $v.redis.existingSecret -}}
{{- $key = $v.redis.existingSecretPasswordKey -}}
{{- else if and (has $var (list "AWS_ACCESS_KEY_ID" "AWS_SECRET_ACCESS_KEY")) $v.storage.s3.existingSecret -}}
{{- $name = $v.storage.s3.existingSecret -}}
{{- $key = ternary $v.storage.s3.existingSecretKeys.accessKeyId $v.storage.s3.existingSecretKeys.secretAccessKey (eq $var "AWS_ACCESS_KEY_ID") -}}
{{- else if and (eq $var "APP_ADMIN_PASSWORD") $v.setup.admin.existingSecret -}}
{{- $name = $v.setup.admin.existingSecret -}}
{{- $key = $v.setup.admin.existingSecretPasswordKey -}}
{{- end -}}
name: {{ $name | quote }}
key: {{ $key | quote }}
{{- end }}

{{/*
One env entry backed by a Secret.
Usage: include "cattr.secretEnv" (dict "context" $ "var" "APP_KEY" "chartSecret" $name)
*/}}
{{- define "cattr.secretEnv" -}}
- name: {{ .var }}
  valueFrom:
    secretKeyRef:
      {{- include "cattr.secretRef" . | nindent 6 }}
{{- end }}
