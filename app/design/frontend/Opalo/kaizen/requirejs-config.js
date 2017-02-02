var config = {
    paths: {            
      "kaizenBootstrap": "js/bootstrap.min",
      "kaizenLocal":     "js/local"
    },   
    shim: {
      "kaizenBootstrap": {
          deps: ["jquery"]
      },
      "kaizenLocal": {
          deps: ["kaizenBootstrap"]
      },
    }
};