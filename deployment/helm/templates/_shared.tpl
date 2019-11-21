{{/*
Q2A Directory
*/}}
{{- define "q2a.q2a-dir" -}}
{{ printf "%s/q2a/" .Values.nginx.root | clean }}
{{- end -}}

{{/*
NGINX and Q2A will share the following volume mounts
*/}}
{{- define "q2a.shared-volume-mounts" -}}
- name: q2a-data
  mountPath: {{ include "q2a.q2a-dir" . }}
{{- end -}}

{{/*
NGINX and Q2A will share the following volumes
*/}}
{{- define "q2a.q2a-shared-volumes" -}}
- name: q2a-data
  persistentVolumeClaim:
    claimName: {{ include "q2a.fullname" . }}-q2a-data
{{- end -}}

