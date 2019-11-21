{{/*
MySQL service subdomain name
*/}}
{{- define "q2a.mysql-service-name" -}}
{{ include "q2a.fullname" . }}-mysql
{{- end -}}

{{/*
NGINX service subdomain name
*/}}
{{- define "q2a.nginx-service-name" -}}
{{ include "q2a.fullname" . }}-nginx
{{- end -}}

{{/*
Q2A service subdomain name
*/}}
{{- define "q2a.q2a-service-name" -}}
{{ include "q2a.fullname" . }}-q2a
{{- end -}}
