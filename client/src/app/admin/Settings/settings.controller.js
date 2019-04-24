/**
 * Created by tmanson on 15/04/2016.
 */

(function () {
  'use strict';

  angular
    .module('redCrossQuestClient')
    .controller('SettingsController', SettingsController);

  /** @ngInject */
  function SettingsController($rootScope, $log, $localStorage, $location,
                                SettingsResource, DateTimeHandlingService)
  {
    var vm = this;
    vm.currentUserRole=$localStorage.currentUser.roleId;
    $rootScope.$emit('title-updated', 'Paramètres');

    //load the local stoarge version first
    vm.settings       = $localStorage.guiSettings;
    vm.deploymentType = $localStorage.currentUser.d;
    //update it with current DB Values


    vm.reload=function()
    {
      SettingsResource.query().$promise.then(handleResult);
      computeURL();
    };

    vm.reload();

    function computeSubDomain()
    {
      switch (vm.deploymentType)
      {
        case 'P': return ''     ;
        case 'T': return 'test.';
        case 'D': return 'dev.' ;

      }
    }
    function computeURL()
    {
      vm.token_benevole_url    = 'https://'+computeSubDomain()+vm.settings.RedQuestDomain+'/registration?uuid='+vm.settings.ul_settings.token_benevole   ;
      vm.token_benevole_1j_url = 'https://'+computeSubDomain()+vm.settings.RedQuestDomain+'/registration?uuid='+vm.settings.ul_settings.token_benevole_1j;
    }



    function handleResult (settings)
    {
      vm.settings = settings;
      computeURL();
      /*
      $log.info("Find '"+settings.length+"' settings");
      vm.settings = settings;
      var counti = settings.length;
      var i=0;
      for(i=0;i<counti;i++)
      {
        vm.settings[i].created      = DateTimeHandlingService.handleServerDate(vm.settings[i].created     ).stringVersion;
        vm.settings[i].updated      = DateTimeHandlingService.handleServerDate(vm.settings[i].updated     ).stringVersion;
      }*/
    }



  }
})();

