{{/*
Generate the same flag suffix as ModuleActivatorService::flagName() in PHP
and moduleFlagName() in JS. Keep the logic in sync with
docs/dev/module-activation-flags.md.
*/}}
{{- define "cattr.moduleFlagName" -}}
{{- $name := regexReplaceAll "([a-z0-9])([A-Z])" . "${1}_${2}" -}}
{{- $name = regexReplaceAll "[^A-Za-z0-9]+" $name "_" -}}
{{- $name = regexReplaceAll "_+" $name "_" -}}
{{- trimAll "_" (upper $name) -}}
{{- end }}

{{/*
Generate module activation environment variables in deterministic order.
Backend and frontend use independent flags; explicit false emits "false".
*/}}
{{- define "cattr.moduleEnv" -}}
{{- range $side := list "backend" "frontend" -}}
{{- $flags := get $.Values.modules $side | default dict -}}
{{- range $id := keys $flags | sortAlpha }}
- name: MODULE_{{ upper $side }}_{{ include "cattr.moduleFlagName" $id }}_ENABLED
  value: {{ get $flags $id | toString | quote }}
{{- end -}}
{{- end -}}
{{- end }}
