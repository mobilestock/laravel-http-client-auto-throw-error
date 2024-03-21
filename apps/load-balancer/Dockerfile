FROM lookpay-api:latest as lookpay-api

FROM debian:buster as builder

RUN apt update \
    && apt install -y wget

RUN wget https://github.com/openresty/echo-nginx-module/archive/refs/tags/v0.63.tar.gz \
    -O /tmp/echo-nginx-module.tar.gz \
    && tar -xzf /tmp/echo-nginx-module.tar.gz -C /tmp

FROM openresty/openresty
ARG RESTY_CONFIG_OPTIONS_MORE='--add-module=/tmp/echo-nginx-module'
COPY --from=builder /tmp /tmp
COPY ./conf.d /usr/local/openresty/nginx/conf.d
COPY nginx.conf /usr/local/openresty/nginx/conf/nginx.conf
COPY request_logger.lua /usr/local/openresty/nginx/
COPY --from=lookpay-api /app /lookpay-api
