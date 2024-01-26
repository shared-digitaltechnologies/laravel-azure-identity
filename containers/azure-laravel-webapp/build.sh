#!/usr/bin/env bash
set -e

# SCRIPT SETUP
cd "$(dirname "$0")"

# INPUT SETTINGS
Version="v0.3.2"
Distros="alpine-3.17"

# Parameters
AZURE_CONTAINER_REGISTRY_NAME=${AZURE_CONTAINER_REGISTRY_NAME:-shrdprocodeplayground}
AZURE_SUBSCRIPTION_ID=${AZURE_SUBSCRIPTION_ID:-"Microsoft Partner Network"}

# Output Files
DistDir="../../../dist/packages/containers/azure-laravel-webapp/docker"

# Referenced Containers
AzureOtelColAgentImage="$AZURE_CONTAINER_REGISTRY_NAME.azurecr.io/azure-otelcol-agent:latest"
DockerPgBouncerImage="$AZURE_CONTAINER_REGISTRY_NAME.azurecr.io/docker-pgbouncer:latest-alpine3.17"
PgBouncerVersion="1.21.0"

# Tags
RepoName="$AZURE_CONTAINER_REGISTRY_NAME.azurecr.io/azure-laravel-webapp"
GitHash="$(git rev-parse --short HEAD)"

# SETUP OUTPUT DIRECTORIES
mkdir -p $DistDir

# LOGIN
az acr login --name "$AZURE_CONTAINER_REGISTRY_NAME" --subscription "$AZURE_SUBSCRIPTION_ID"

build-docker-image() {
    LatestTag="$RepoName:latest-$TagSuffix"
    VersionTag="$RepoName:$Version-$TagSuffix"
    GitHashTag="$RepoName:sha-$GitHash-$TagSuffix"

    IIDFile="$DistDir/$TagSuffix-iid.txt"
    MetadataFile="$DistDir/$TagSuffix-metadata.json"
    VersionTagFile="$DistDir/$TagSuffix-version-tag.txt"
    GitHashTagFile="$DistDir/$TagSuffix-githash-tag.txt"

    echo "BUILDING IMAGE [$TagSuffix]"
    echo "   tags: - $LatestTag"
    echo "         - $VersionTag"
    echo "         - $GitHashTag"
    echo "   params: $*"
    echo "   iidfile:       $IIDFile"
    echo "   metadata-file: $MetadataFile"
    echo " "

    docker buildx build \
        --build-context common='./common' \
        --iidfile "$IIDFile" \
        --metadata-file "$MetadataFile" \
        --tag "$LatestTag" \
        --tag "$VersionTag" \
        --tag "$GitHashTag" \
        "$@"

    echo "$VersionTag" > "$VersionTagFile"
    echo "$GitHashTag" > "$GitHashTagFile"

    PrevImageId="$GitHashTag"

    echo "DONE BUILDING IMAGE [$TagSuffix] $IIDFile"
}

for Distro in $Distros; do

    if [ "$Distro" = "alpine-3.17" ]; then
        DTag="alpine3.17"
    else
        DTag="$Distro"
    fi

    # BUILD basic
    TagSuffix="basic-$DTag"
    build-docker-image \
        --file "./basic/$Distro.Dockerfile" \
        --push \
        "./basic"
    BasicImageId=$PrevImageId

    # BUILD basic-debug
    TagSuffix="basic-debug-$DTag"
    build-docker-image \
        --build-arg "BASIC_IMAGE=$BasicImageId" \
        --file "./basic/debug.Dockerfile" \
        --push \
        "./basic"

    # BUILD ready2go
    TagSuffix="ready2go-$DTag"
    build-docker-image \
        --build-arg "BASIC_IMAGE=$BasicImageId" \
        --file "./ready2go/$Distro.Dockerfile" \
        --push \
        "./ready2go"
    Ready2GoId=$PrevImageId

    # BUILD ready2go-debug
    TagSuffix="ready2go-debug-$DTag"
    build-docker-image \
        --build-arg "READY2GO_IMAGE=$Ready2GoId" \
        --file "./ready2go/debug.Dockerfile" \
        --push \
        "./ready2go"

    # BUILD allin1
    TagSuffix="allin1-$DTag"
    build-docker-image \
        --build-arg "READY2GO_IMAGE=$Ready2GoId" \
        --build-arg "AZURE_OTELCOL_AGENT_IMAGE=$AzureOtelColAgentImage" \
        --build-arg "DOCKER_PGBOUNCER_IMAGE=$DockerPgBouncerImage" \
        --build-arg "PGBOUNCER_VERSION=$PgBouncerVersion" \
        --file "./allin1/$Distro.Dockerfile" \
        --push \
        "./allin1"
    AllIn1Id=$PrevImageId

    # BUILD allin1-debug
    TagSuffix="allin1-debug-$DTag"
    build-docker-image \
        --build-arg "ALLIN1_IMAGE=$AllIn1Id" \
        --file "./allin1/debug.Dockerfile" \
        --push \
        "./allin1"

done
