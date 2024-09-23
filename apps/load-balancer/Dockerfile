FROM lookpay-api:latest AS lookpay-api
FROM med-api:latest AS med-api

FROM openresty/openresty
COPY ./conf.d /usr/local/openresty/nginx/conf.d
COPY nginx.conf /usr/local/openresty/nginx/conf/nginx.conf
COPY request_logger.lua /usr/local/openresty/nginx/
COPY --from=lookpay-api /lookpay-api /lookpay-api
COPY --from=med-api /med-api /med-api
