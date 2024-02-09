describe("Deve gravar log de acesso em arquivo", function()
    local io_open_original = io.open

    function io.open(path, mode)
        return {
            write = function(self, log)
                assert.are.same('tem que gravar isso daqui.' .. '\n', log)
            end,
            close = function() end
        }, nil
    end

    package.loaded['cjson'] = {
        encode = function()
            return 'tem que gravar isso daqui.'
        end
    }
    
    local ngx = {
        var={
            uri="/uri",
            request_body="corpo da requisição",
            upstream_response_time=112
        },
        req={
            get_headers=function() return {["header"]="value"} end,
            start_time=function() return 123 end,
            get_method=function() return "GET" end,
            get_uri_args=function() return {["arg"]="value"} end,
            get_post_args=function() return {["arg"]="value"} end,
        },
        resp={
            get_headers=function() return {["header"]="value"} end,
        },
        now=function() return 123 end,
        status=200,
    }
    _G.ngx = ngx
    dofile('request_logger.lua')
end)
