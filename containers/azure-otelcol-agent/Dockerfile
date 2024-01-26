################################################################################################
# OTELCOL BUILDER STAGE                                                                        #
################################################################################################

FROM golang:1.21-alpine3.17 AS build-stage
# This stage is just used to build an otelcol

WORKDIR /usr/src/otelcol

# Install the otelcol builder
RUN go install go.opentelemetry.io/collector/cmd/builder@latest

# Copy the builder config into the container.
COPY ./builder-config.yaml ./builder-config.yaml

# Build the otelcol command
RUN builder --config=builder-config.yaml

################################################################################################
# OTELCOL                                                                                      #
################################################################################################

FROM alpine:3.17 AS release

WORKDIR /usr/local/src/otelcol/

COPY --from=build-stage /usr/src/otelcol/ /usr/local/src/otelcol/

EXPOSE 1888 8888 8889 13133 4317 4318 55679

ENTRYPOINT ["/usr/local/src/otelcol/otelcol"]
