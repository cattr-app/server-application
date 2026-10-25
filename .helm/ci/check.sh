#!/bin/sh
# Static checks for the Helm chart: helm lint, helm template for every
# ci/*-values.yaml (twice: output must be identical), kubeconform -strict
# against several Kubernetes versions, and renders that must fail.
#
# Needs helm and kubeconform (e.g. the alpine/k8s image). Run from the
# repository root:  sh .helm/ci/check.sh
set -eu

chart=.helm
out="${CHECK_OUT:-$(mktemp -d)}"
kube_versions="${KUBE_VERSIONS:-1.29.0 1.34.0}"
gateway_api="--api-versions gateway.networking.k8s.io/v1/HTTPRoute"
crd_catalog='https://raw.githubusercontent.com/datreeio/CRDs-catalog/main/{{.Group}}/{{.ResourceKind}}_{{.ResourceAPIVersion}}.json'
status=0

for values in "$chart"/ci/*-values.yaml; do
    name="$(basename "$values" -values.yaml)"
    extra=""
    case "$name" in httproute) extra="$gateway_api" ;; esac

    echo "::group::$name"
    helm lint --strict "$chart" -f "$values" || status=1
    # shellcheck disable=SC2086
    helm template cattr "$chart" --namespace cattr -f "$values" $extra > "$out/$name.yaml" || { status=1; echo "::endgroup::"; continue; }
    # shellcheck disable=SC2086
    helm template cattr "$chart" --namespace cattr -f "$values" $extra > "$out/$name.again.yaml"
    if ! cmp -s "$out/$name.yaml" "$out/$name.again.yaml"; then
        echo "ERROR: $name renders differently on the second run"
        status=1
    fi
    for version in $kube_versions; do
        kubeconform -strict -summary -kubernetes-version "$version" \
            -schema-location default -schema-location "$crd_catalog" \
            "$out/$name.yaml" || status=1
    done
    echo "::endgroup::"
done

# name|expected message fragment|helm template arguments
base="-f $chart/ci/default-values.yaml"
while IFS='|' read -r name expected args; do
    [ -n "$name" ] || continue
    # shellcheck disable=SC2086
    if result="$(helm template cattr "$chart" $args 2>&1)"; then
        echo "ERROR: render '$name' succeeded but must fail"
        status=1
    elif ! printf '%s' "$result" | grep -qF -- "$expected"; then
        echo "ERROR: render '$name' failed without '$expected':"
        printf '%s\n' "$result" | tail -3
        status=1
    else
        echo "ok: '$name' fails with '$expected'"
    fi
done <<EOF
missing appKey|APP_KEY is required|$base --set cattr.appKey=
appKey not 32 bytes|APP_KEY must be a base64-encoded 32-byte key|$base --set cattr.appKey=base64:c2hvcnQ=
missing appUrl|cattr.appUrl is required|$base --set cattr.appUrl=
no database host|database.host is required|$base --set database.host=
s3 without redis|requires an external Redis|$base --set storage.type=s3 --set storage.s3.bucket=b
two web replicas|web.replicas > 1 is not supported|-f $chart/ci/s3-redis-values.yaml --set web.replicas=2
scaled pvc mode|web.replicas and queue.replicas must be 1|$base --set queue.replicas=2
admin without credentials|setup.admin.enabled=true needs|$base --set setup.admin.password=
httpRoute without Gateway API|does not serve gateway.networking.k8s.io/v1 HTTPRoute|-f $chart/ci/httproute-values.yaml
chart 0.1.x values|additional properties|$base --set replicaCount=2
EOF

exit "$status"
