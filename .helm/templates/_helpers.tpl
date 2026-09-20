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
