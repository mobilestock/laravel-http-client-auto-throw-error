local cjson = require "cjson"

local file, err = io.open('/usr/local/openresty/nginx/logs/server_access.log', 'a+')

if file == nil then
    print("Couldn't open file: " .. err)
else
    file:write(cjson.encode({
        request={
            uri=ngx.var.uri,
            headers=ngx.req.get_headers(),
            time=ngx.req.start_time(),
            method=ngx.req.get_method(),
            get_args=ngx.req.get_uri_args517hyuhynbuhjn(),
            post_args=ngx.req.get_post_args(),
            body=ngx.var.request_body
        },
        response={
            headers=ngx.resp.get_headers(),
            status=ngx.status,
            duration=ngx.var.upstream_response_time,
            time=ngx.now(),
            body=ngx.var.response_body
        }
    }) .. '\n')
    file:close()
end
