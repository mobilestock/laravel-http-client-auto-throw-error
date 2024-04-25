let recursiveFindMixin = {
    methods: {
        recursiveFind(data, id) {
            for (var i = 0; i < data.length; i++) {
                if (data[i].id == id) {
                    return data[i];
                } else if (data[i].children && data[i].children.length) {
                    if ((obj = this.recursiveFind(data[i].children, id))) return obj;
                }
            }
        },
    }
};
