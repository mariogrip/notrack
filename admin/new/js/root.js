var app = angular.module("notrack", ["ngRoute"]);

app.controller("rootCtrl", function ($scope) {
  //
});

app.controller("tldblocklistCtrl", ["$scope", "$http", function ($scope, $http) {
  var blocklist = $http.get("/admin/api/tldblocklist.php").then(function (i) {
    $scope.blocklist = i.data;
  });

  $scope.searchStr = "";
  var searchStr;

  var updateSearchStr = function () {
    $scope.searchStr = $scope.searchInputStr;
    $scope.$apply();
  }

  var bounce = _.debounce(updateSearchStr, 150);
  $scope.$watch("searchInputStr", function () {
    bounce();
  });

}]);

app.controller("blocklistCtrl", ["$scope", "$http", "$routeParams", function ($scope, $http, $routeParams) {
  var blocklistTotalCount = 1;

  var getBlocklist = function (start, count) {
    var request = {
      method: 'GET',
      url: "/admin/api/blocklist.php",
      params: {
        count: count,
        start: start
      }
    }
    return $http(request).then(function (i) {
      $scope.blocklist = i.data;
    });
  }

  var getBlocklistTotalCount = function () {
    var request = {
      method: 'GET',
      url: "/admin/api/blocklist.php",
      params: {
        total: 1
      }
    }
    $http(request).then(function (i) {
      blocklistTotalCount = i.data;
    });
  }

  getBlocklist("0", '200').then(function () {
    getBlocklist("0", "-1");
  });


  $scope.searchStr = "";
  var searchStr;

  var updateSearchStr = function () {
    $scope.searchStr = $scope.searchInputStr;
    $scope.$apply();
  }

  var bounce = _.debounce(updateSearchStr, 150);

  $scope.$watch("searchInputStr", function () {
    bounce();
  });

}]);

app.controller("statsCtrl", ["$scope", "$http", function ($scope, $http) {
  var request = function () {
    $http.get("/admin/api/stats.php").then(function (i) {
      $scope.domains = i.data;
    });
  }
  request();

  setInterval(request, 10000);

  $scope.order = '-url'

  $scope.setOrder = function (order) {
      $scope.order = $scope.order.startsWith('-') && $scope.order.replace("-", "") === order ? order : '-'+order;
  }

  $scope.searchStr = "";
  var searchStr;

  var updateSearchStr = function () {
    $scope.searchStr = $scope.searchInputStr;
    $scope.$apply();
  }

  var bounce = _.debounce(updateSearchStr, 150);

  $scope.$watch("searchInputStr", function () {
    bounce();
  });
}]);

app.controller("upgradeCtrl", ["$scope", "$http", function ($scope, $http) {
  $http.get("/admin/api/upgrade.php").then(function (i) {
    $scope.upgradeAvailable = i.data.version !== i.data.latestVersion;
    $scope.versionData = i.data;
  })
}]);


app.controller("dhcpleasesCtrl", ["$scope", "$http", function ($scope, $http) {
  $http.get("/admin/api/dhcpleases.php").then(function (i) {
    $scope.dhcpleases = i.data;
  }).catch(function (err) {
    $scope.notInUse = err.status === 404;
  });

  $scope.searchStr = "";
  var searchStr;

  var updateSearchStr = function () {
    $scope.searchStr = $scope.searchInputStr;
    $scope.$apply();
  }

  var bounce = _.debounce(updateSearchStr, 150);

  $scope.$watch("searchInputStr", function () {
    bounce();
  });
}]);

app.config(["$routeProvider", function ($routeProvider) {
  $routeProvider.
    when("/blocklist", {
      templateUrl: "/admin/new/partials/blocklist.html",
      controller: "blocklistCtrl"
    }).
    when("/tldblocklist", {
      templateUrl: "/admin/new/partials/tldblocklist.html",
      controller: "tldblocklistCtrl"
    }).
    when("/stats", {
      templateUrl: "/admin/new/partials/stats.html",
      controller: "statsCtrl"
    }).
    when("/dhcpleases", {
      templateUrl: "/admin/new/partials/dhcpleases.html",
      controller: "dhcpleasesCtrl"
    }).
    when("/upgrade", {
      templateUrl: "/admin/new/partials/upgrade.html",
      controller: "upgradeCtrl"
    }).
    otherwise({redirectTo: "/blocklist"})
}]);
