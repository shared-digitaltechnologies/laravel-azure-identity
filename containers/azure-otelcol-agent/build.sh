#!/usr/bin/env bash
set -e

# INPUT SETTINGS
Version="v0.1.0"

# Parameters
AZURE_CONTAINER_REGISTRY_NAME=${AZURE_CONTAINER_REGISTRY_NAME:-shrdprocodeplayground}
AZURE_SUBSCRIPTION_ID=${AZURE_SUBSCRIPTION_ID:-"Microsoft Partner Network"}

# Output Files
DistDir="../../../dist/packages/containers/azure-otelcol-agent/docker"

# Tags
RepoName="$AZURE_CONTAINER_REGISTRY_NAME.azurecr.io/azure-otelcol-agent"
GitHash="$(git rev-parse --short HEAD)"

LatestTag="$RepoName:latest"
VersionTag="$RepoName:$Version"
GitHashTag="$RepoName:sha-$GitHash"

IIDFile="$DistDir/iid.txt"
MetadataFile="$DistDir/metadata.json"
VersionTagFile="$DistDir/version-tag.txt"
GitHashTagFile="$DistDir/githash-tag.txt"

az acr login --name "$AZURE_CONTAINER_REGISTRY_NAME" --subscription "$AZURE_SUBSCRIPTION_ID"

docker buildx build \
    --file Dockerfile \
    --iidfile "$IIDFile" \
    --metadata-file "$MetadataFile" \
    --tag "$LatestTag" \
    --tag "$VersionTag" \
    --tag "$GitHashTag" \
    --push \
    .

echo "$VersionTag" > "$VersionTagFile"
echo "$GitHashTag" > "$GitHashTagFile"
