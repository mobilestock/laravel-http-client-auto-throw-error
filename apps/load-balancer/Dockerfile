FROM lookpay-api:latest as lookpay-api
FROM colaborador-central:latest AS colaborador-central

FROM openresty/openresty
COPY ./conf.d /usr/local/openresty/nginx/conf.d
COPY nginx.conf /usr/local/openresty/nginx/conf/nginx.conf
COPY request_logger.lua /usr/local/openresty/nginx/
COPY --from=lookpay-api /lookpay-api /lookpay-api
COPY --from=colaborador-central /colaborador-central /colaborador-central
