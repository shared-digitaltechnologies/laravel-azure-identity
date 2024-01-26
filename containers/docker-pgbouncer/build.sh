#!/usr/bin/env bash
set -e

# INPUT SETTINGS
Version="v0.1.0"
Distro="alpine3.17"
PgBouncerVersion="1.21.0"

# Parameters
AZURE_CONTAINER_REGISTRY_NAME=${AZURE_CONTAINER_REGISTRY_NAME:-shrdprocodeplayground}
AZURE_SUBSCRIPTION_ID=${AZURE_SUBSCRIPTION_ID:-"Microsoft Partner Network"}

# Output Files
DistDir="../../../dist/packages/containers/azure-otelcol-agent/docker"
mkdir -p "$DistDir"

# Tags
RepoName="$AZURE_CONTAINER_REGISTRY_NAME.azurecr.io/docker-pgbouncer"
GitHash="$(git rev-parse --short HEAD)"
TagPrefix="$Distro"

LatestTag="$RepoName:latest-$TagPrefix"
VersionTag="$RepoName:$Version-$TagPrefix"
GitHashTag="$RepoName:sha-$GitHash-$TagPrefix"

IIDFile="$DistDir/$TagPrefix-iid.txt"
MetadataFile="$DistDir/$TagPrefix-metadata.json"
VersionTagFile="$DistDir/$TagPrefix-version-tag.txt"
GitHashTagFile="$DistDir/$TagPrefix-githash-tag.txt"

az acr login --name "$AZURE_CONTAINER_REGISTRY_NAME" --subscription "$AZURE_SUBSCRIPTION_ID"

docker buildx build \
    --file ./Dockerfile \
    --build-arg "BASE_IMAGE=alpine:3.17" \
    --build-arg "PGBOUNCER_VERSION=$PgBouncerVersion" \
    --build-context "common=./common" \
    --iidfile "$IIDFile" \
    --metadata-file "$MetadataFile" \
    --tag "$LatestTag" \
    --tag "$VersionTag" \
    --tag "$GitHashTag" \
    --push \
    "alpine"

echo "$VersionTag" > "$VersionTagFile"
echo "$GitHashTag" > "$GitHashTagFile"
