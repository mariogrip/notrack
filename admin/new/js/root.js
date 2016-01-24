var app = angular.module("notrack", ["ngRoute"]);

app.controller("rootCtrl", function ($scope) {
  $scope
});

app.controller("tldblocklistCtrl", ["$scope", "$http", function ($scope, $http) {
  var blocklist = $http.get("/api/tldblocklist.php").then(function (i) {
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
  var blocklist = $http.get("/api/blocklist.php").then(function (i) {
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
  var blocklist = $http.get("/api/dhcpleases.php").then(function (i) {
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

app.config(["$routeProvider", function ($routeProvider) {
  $routeProvider.
    when("/blocklist", {
      templateUrl: "/new/partials/blocklist.html",
      controller: "blocklistCtrl"
    }).
    when("/tldblocklist", {
      templateUrl: "/new/partials/tldblocklist.html",
      controller: "tldblocklistCtrl"
    }).
    when("/stats", {
      templateUrl: "/new/partials/stats.html",
      controller: "statsCtrl"
    }).
    when("/dhcpleases", {
      templateUrl: "/new/partials/dhcpleases.html",
      controller: "dhcpleasesCtrl"
    }).
    when("/upgrade", {
      templateUrl: "/new/partials/upgrade.html",
      controller: "upgradeCtrl"
    }).
    otherwise({redirectTo: "/blocklist"})
}]);
