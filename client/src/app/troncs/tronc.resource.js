/**
 * Created by tmanson on 03/05/2016.
 */

angular.module('redCrossQuestClient').factory('TroncResource', function($resource, $localStorage) {
  return $resource('/rest/:roleId/ul/:ulId/troncs/:id',
    {
      roleId: $localStorage.currentUser.roleId,
      ulId  : $localStorage.currentUser.ulId,
      id    : '@id'
    }, {
    update: {
      method: 'PUT' // this method issues a PUT request
    }
  });
});
