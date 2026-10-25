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
Labels shared by every resource, without a component.
*/}}
{{- define "cattr.commonLabels" -}}
helm.sh/chart: {{ include "cattr.chart" . }}
app.kubernetes.io/version: {{ .Chart.AppVersion | quote }}
app.kubernetes.io/managed-by: {{ .Release.Service }}
app.kubernetes.io/part-of: cattr
{{- with .Values.commonLabels }}
{{ toYaml . }}
{{- end }}
{{- end }}

{{/*
Common labels (component "app", as in chart 0.1.x).
*/}}
{{- define "cattr.labels" -}}
{{ include "cattr.selectorLabels" . }}
{{ include "cattr.commonLabels" . }}
{{- end }}

{{/*
Selector labels of the pvc-mode Deployment `<fullname>` and of its Service.
Kept byte-identical to chart 0.1.x: Deployment selectors are immutable.
*/}}
{{- define "cattr.selectorLabels" -}}
app.kubernetes.io/name: {{ include "cattr.name" . }}
app.kubernetes.io/instance: {{ .Release.Name }}
app.kubernetes.io/component: app
{{- end }}

{{/*
Selector labels of one component.
Usage: include "cattr.componentSelectorLabels" (dict "context" $ "component" "web")
*/}}
{{- define "cattr.componentSelectorLabels" -}}
app.kubernetes.io/name: {{ include "cattr.name" .context }}
app.kubernetes.io/instance: {{ .context.Release.Name }}
app.kubernetes.io/component: {{ .component }}
{{- end }}

{{/*
Labels of one component.
*/}}
{{- define "cattr.componentLabels" -}}
{{ include "cattr.componentSelectorLabels" . }}
{{ include "cattr.commonLabels" .context }}
{{- end }}

{{/*
Selector labels of the Pods serving HTTP (Service `<fullname>`):
the single Pod in pvc mode, the web Pods in s3 mode.
*/}}
{{- define "cattr.webSelectorLabels" -}}
{{- if eq .Values.storage.type "s3" }}
{{- include "cattr.componentSelectorLabels" (dict "context" . "component" "web") }}
{{- else }}
{{- include "cattr.selectorLabels" . }}
{{- end }}
{{- end }}

{{/*
MySQL fully qualified name.
*/}}
{{- define "cattr.mysql.fullname" -}}
{{- printf "%s-mysql" (include "cattr.fullname" .) }}
{{- end }}

{{/*
Database host: database.host, or the built-in MySQL Service.
*/}}
{{- define "cattr.mysql.host" -}}
{{- if .Values.database.host }}
{{- .Values.database.host }}
{{- else if .Values.mysql.enabled }}
{{- include "cattr.mysql.fullname" . }}
{{- end }}
{{- end }}

{{/*
MySQL labels.
*/}}
{{- define "cattr.mysql.labels" -}}
{{ include "cattr.mysql.selectorLabels" . }}
helm.sh/chart: {{ include "cattr.chart" . }}
app.kubernetes.io/version: {{ .Values.mysql.image.tag | quote }}
app.kubernetes.io/managed-by: {{ .Release.Service }}
app.kubernetes.io/part-of: cattr
{{- with .Values.commonLabels }}
{{ toYaml . }}
{{- end }}
{{- end }}

{{/*
MySQL selector labels (byte-identical to chart 0.1.x).
*/}}
{{- define "cattr.mysql.selectorLabels" -}}
app.kubernetes.io/name: {{ include "cattr.name" . }}
app.kubernetes.io/instance: {{ .Release.Name }}
app.kubernetes.io/component: mysql
{{- end }}

{{/*
Name of the chart-managed Secret (kept from chart 0.1.x).
*/}}
{{- define "cattr.secretName" -}}
{{- include "cattr.fullname" . }}
{{- end }}

{{/*
Name of the setup hook Secret.
*/}}
{{- define "cattr.setupSecretName" -}}
{{- printf "%s-setup" (include "cattr.fullname" .) }}
{{- end }}

{{/*
Image reference: registry/repository:tag[@digest].
Usage: include "cattr.imageRef" .Values.image (tag fallback given as "default")
*/}}
{{- define "cattr.imageRef" -}}
{{- $ref := printf "%s/%s:%s" .image.registry .image.repository (.image.tag | default .default | toString) -}}
{{- if .image.digest -}}
{{- $ref = printf "%s@%s" $ref .image.digest -}}
{{- end -}}
{{- $ref -}}
{{- end }}

{{- define "cattr.image" -}}
{{- include "cattr.imageRef" (dict "image" .Values.image "default" .Chart.AppVersion) }}
{{- end }}

{{- define "cattr.mysql.image" -}}
{{- include "cattr.imageRef" (dict "image" .Values.mysql.image "default" "") }}
{{- end }}

{{/*
Common annotations.
*/}}
{{- define "cattr.annotations" -}}
{{- with .Values.commonAnnotations }}{{ toYaml . }}{{ end }}
{{- end }}

{{/*
Render the storageClass for a given persistence block.
*/}}
{{- define "cattr.storageClass" -}}
{{- $storageClass := .persistence.storageClass | default .global.storageClass -}}
{{- if $storageClass -}}
storageClassName: {{ $storageClass | quote }}
{{- end }}
{{- end }}

{{/*
imagePullSecrets list.
*/}}
{{- define "cattr.imagePullSecrets" -}}
{{- with (concat (.Values.global.imagePullSecrets | default list) (.Values.imagePullSecrets | default list)) }}
imagePullSecrets:
  {{- toYaml . | nindent 2 }}
{{- end }}
{{- end }}

{{/*
ServiceAccount name.
*/}}
{{- define "cattr.serviceAccountName" -}}
{{- if .Values.serviceAccount.create }}
{{- .Values.serviceAccount.name | default (include "cattr.fullname" .) }}
{{- else }}
{{- .Values.serviceAccount.name | default "default" }}
{{- end }}
{{- end }}

{{/*
Name of the Reverb Service used by queue/scheduler in s3 mode.
*/}}
{{- define "cattr.reverbServiceName" -}}
{{- printf "%s-reverb" (include "cattr.fullname" .) }}
{{- end }}

{{/*
Helm hook events of the setup Job and its Secret. With the built-in demo MySQL
the database only exists after install, so the install hook is post-install.
*/}}
{{- define "cattr.setup.hookEvents" -}}
{{- if .Values.mysql.enabled -}}
post-install,pre-upgrade
{{- else -}}
pre-install,pre-upgrade
{{- end -}}
{{- end }}
