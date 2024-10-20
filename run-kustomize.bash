#!/usr/bin/env bash

failure=0

# shellcheck disable=SC2016
find deploy/kustomize -type f -name "kustomization.yaml" -print0 | xargs -0 -P 4 -I {} sh -c 'kustomize build "$(dirname {})" | kube-score score -' || failure=1

exit ${failure}
