{{/*
Expand the name of the chart.
*/}}
{{- define "cattr.name" -}}
{{- default .Chart.Name .Values.nameOverride | trunc 63 | trimSuffix "-" }}
{{- end }}

{{/*
Create a default fully qualified app name.
*/}}
{{- define "cattr.fullname" -}}
{{- if .Values.fullnameOverride }}
{{- .Values.fullnameOverride | trunc 63 | trimSuffix "-" }}
{{- else }}
{{- $name := default .Chart.Name .Values.nameOverride }}
{{- if contains $name .Release.Name }}
{{- .Release.Name | trunc 63 | trimSuffix "-" }}
{{- else }}
{{- printf "%s-%s" .Release.Name $name | trunc 63 | trimSuffix "-" }}
{{- end }}
{{- end }}
{{- end }}

{{/*
Create chart name and version as used by the chart label.
*/}}
{{- define "cattr.chart" -}}
{{- printf "%s-%s" .Chart.Name .Chart.Version | replace "+" "_" | trunc 63 | trimSuffix "-" }}
{{- end }}

{{/*
Common labels.
*/}}
{{- define "cattr.labels" -}}
helm.sh/chart: {{ include "cattr.chart" . }}
{{ include "cattr.selectorLabels" . }}
app.kubernetes.io/version: {{ .Chart.AppVersion | quote }}
app.kubernetes.io/managed-by: {{ .Release.Service }}
{{- with .Values.commonLabels }}
{{ toYaml . }}
{{- end }}
{{- end }}

{{/*
Selector labels.
*/}}
{{- define "cattr.selectorLabels" -}}
app.kubernetes.io/name: {{ include "cattr.name" . }}
app.kubernetes.io/instance: {{ .Release.Name }}
app.kubernetes.io/component: app
{{- end }}

{{/*
MySQL fully qualified name.
*/}}
{{- define "cattr.mysql.fullname" -}}
{{- printf "%s-mysql" (include "cattr.fullname" .) }}
{{- end }}

{{/*
MySQL host.
*/}}
{{- define "cattr.mysql.host" -}}
{{- if .Values.database.host }}
{{- .Values.database.host }}
{{- else }}
{{- include "cattr.mysql.fullname" . }}
{{- end }}
{{- end }}

{{/*
MySQL labels.
*/}}
{{- define "cattr.mysql.labels" -}}
helm.sh/chart: {{ include "cattr.chart" . }}
{{ include "cattr.mysql.selectorLabels" . }}
app.kubernetes.io/version: {{ .Values.mysql.image.tag | quote }}
app.kubernetes.io/managed-by: {{ .Release.Service }}
{{- with .Values.commonLabels }}
{{ toYaml . }}
{{- end }}
{{- end }}

{{/*
MySQL selector labels.
*/}}
{{- define "cattr.mysql.selectorLabels" -}}
app.kubernetes.io/name: {{ include "cattr.name" . }}
app.kubernetes.io/instance: {{ .Release.Name }}
app.kubernetes.io/component: mysql
{{- end }}

{{/*
Secret name.
*/}}
{{- define "cattr.secretName" -}}
{{- include "cattr.fullname" . }}
{{- end }}

{{/*
Cattr image.
*/}}
{{- define "cattr.image" -}}
{{- printf "%s/%s:%s" .Values.image.registry .Values.image.repository (.Values.image.tag | default .Chart.AppVersion) }}
{{- end }}

{{/*
MySQL image.
*/}}
{{- define "cattr.mysql.image" -}}
{{- printf "%s/%s:%s" .Values.mysql.image.registry .Values.mysql.image.repository .Values.mysql.image.tag }}
{{- end }}

{{/*
Common annotations.
*/}}
{{- define "cattr.annotations" -}}
{{- with .Values.commonAnnotations }}
{{ toYaml . }}
{{- end }}
{{- end }}

{{/*
Render the storageClass for a given persistence block.
*/}}
{{- define "cattr.storageClass" -}}
{{- $storageClass := .persistence.storageClass | default .global.storageClass -}}
{{- if $storageClass }}
storageClassName: {{ $storageClass | quote }}
{{- end }}
{{- end }}

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
{{- .value | b64enc -}}
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
"base64:..." key; the "base64:" prefix is added only when missing.
*/}}
{{- define "cattr.secret.appKey" -}}
{{- $appKey := .Values.cattr.appKey | default "" -}}
{{- if and $appKey (not (hasPrefix "base64:" $appKey)) -}}
{{- $appKey = printf "base64:%s" $appKey -}}
{{- end -}}
{{- include "cattr.secretValue" (dict "context" . "key" "APP_KEY" "value" $appKey "hint" "set cattr.appKey to a Laravel application key, e.g. the output of `php artisan key:generate --show`") -}}
{{- end }}

{{/*
REVERB_APP_SECRET Secret entry.
*/}}
{{- define "cattr.secret.reverbAppSecret" -}}
{{- include "cattr.secretValue" (dict "context" . "key" "REVERB_APP_SECRET" "value" (.Values.cattr.reverbAppSecret | default "") "hint" "set cattr.reverbAppSecret to a random secret, e.g. the output of `openssl rand -hex 32`") -}}
{{- end }}
