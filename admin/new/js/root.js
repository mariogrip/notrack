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

app.controller("blocklistCtrl", ["$scope", "$http", function ($scope, $http) {
  var blocklist = $http.get("/admin/api/blocklist.php").then(function (i) {
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

app.controller("statsCtrl", ["$scope", "$http", function ($scope, $http) {
  // body...
}]);

app.controller("dhcpleasesCtrl", ["$scope", "$http", function ($scope, $http) {
  var dhcpleases = $http.get("/admin/api/dhcpleases.php").then(function (i) {
    $scope.dhcpleases = i.data;
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
