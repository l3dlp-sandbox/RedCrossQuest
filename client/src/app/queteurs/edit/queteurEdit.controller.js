/**
 * Created by tmanson on 15/04/2016.
 */

(function() {
  'use strict';

  angular
    .module('redCrossQuestClient')
    .controller('QueteurEditController', QueteurEditController);

  /** @ngInject */
  function QueteurEditController($scope, $log, $routeParams, $location, $localStorage, $timeout,
                                 QueteurResource, UserResource, TroncQueteurResource, moment, Upload)
  {
    var vm = this;

    var queteurId = $routeParams.id;
    vm.currentUserRole=$localStorage.currentUser.roleId;


    vm.youngestBirthDate=moment().subtract(10 ,'years').toDate();
    vm.oldestBirthDate  =moment().subtract(100,'years').toDate();


    vm.typeBenevoleList=[
      {id:1,label:'Action Sociale'},
      {id:2,label:'Secours'},
      {id:3,label:'Non Bénévole'},
      {id:4,label:'Ancien Bénévole, Inactif ou Adhérent'},
      {id:5,label:'Commerçant'},
      {id:6,label:'Spécial'}
    ];

    vm.roleList=[
      {id:1,label:'Lecture Seule'},
      {id:2,label:'Opérateur'},
      {id:3,label:'Compteur'},
      {id:4,label:'Admin Local'},
      {id:9,label:'Super Admin'}
    ];

    vm.roleDesc=[];
    vm.roleDesc[1] = 'Consultation des quêteurs et des graphiques publiques';
    vm.roleDesc[2] = 'Liste/Ajout/Update des quêteurs, préparation/départ/retour Troncs, graphiques opérationnel';
    vm.roleDesc[3] = 'Opérateur + Comptage des troncs et tous les graphiques';
    vm.roleDesc[4] = 'Compteur + administration des utilisateurs et paramétrage de RCQ pour l\'UL';
    vm.roleDesc[9] = 'Le grand manitou';

    vm.createNewUser=function()
    {
      vm.current          = new QueteurResource();
      vm.current.ul_id    = vm.ulId;
      vm.current.ul_name  = $localStorage.currentUser.ulName;

    };


    vm.handleDate = function (theDate)
    {
      if(theDate ===null)
        return null;

      var dateAsString = theDate.date;
      return moment( dateAsString.substring (0, dateAsString.length  - 3 ),"YYYY-MM-DD  HH:mm:ss.SSS");
    };


    if (angular.isDefined(queteurId))
    {
      QueteurResource.get({ 'id': queteurId }).$promise.then(function(queteur)
      {
        vm.current = queteur;

        if(queteur.referent_volunteer_entity != null)
        {
          vm.current.referent_volunteerQueteur = queteur.referent_volunteer_entity.first_name+' '+queteur.referent_volunteer_entity.last_name + ' - '+queteur.referent_volunteer_entity.nivol;
        }

        if(typeof vm.current.mobile === "string")
        {
          if(vm.current.mobile === "N/A")
          {
            vm.current.mobile = null;
          }
          try
          {
            vm.current.mobile = parseInt(vm.current.mobile.slice(1));
          }
          catch(e)
          {
            vm.current.mobile = null;
          }

        }

        /*lack of data with previous model (minor instead of birthdate), only for ULParisIV, minor and major where set fixed birthdate
        * if editing one of these ==> set birthdate to null to force user to update the data*/

        if(angular.isDefined(vm.current.birthdate))
        {
          var birthdate = vm.current.birthdate.date.toLocaleString().substr(0,10);

          if(birthdate === '1902-02-02' || birthdate === '2007-07-07')
          {
            vm.current.birthdate = null;
          }
        }



        TroncQueteurResource.getTroncsOfQueteur({'queteur_id': queteurId}).$promise.then(
          function success(data)
          {
            var dataLength = data.length;
            for(var i=0;i<dataLength;i++)
            {
              data[i].depart            = vm.handleDate(data[i].depart);
              data[i].depart_theorique  = vm.handleDate(data[i].depart_theorique);
              data[i].retour            = vm.handleDate(data[i].retour);

              if(data[i].retour !==null && data[i].depart !== null)
              {
                data[i].duration = moment.duration(data[i].retour.diff(data[i].depart)).asMinutes();
              }
            }

            vm.current.troncs_queteur  = data;
          },
          function error(error)
          {
            $log.error(error);
          }

        );

        if(angular.isDefined(vm.current.birthdate))
        {
          vm.current.birthdate = moment( queteur.birthdate.date.substring(0, queteur.birthdate.date.length -16 ),"YYYY-MM-DD").toDate();
          vm.computeAge();
        }

      });

    }
    else
    {
      vm.createNewUser();
    }

    function savedSuccessfully()
    {
      vm.savedSuccessfully=true;

      $timeout(function () { vm.savedSuccessfully=false; }, 5000);
    }

    function errorWhileSaving(error)
    {
      vm.errorWhileSaving=true;
      vm.errorWhileSavingDetails=error;
    }

    vm.uploadFiles=function()
    {
      var queteurId=vm.current.id;


      var upload = Upload.upload({
        url: "/rest/"+ $localStorage.currentUser.roleId+"/ul/"+ $localStorage.currentUser.ulId+"/queteurs/"+queteurId+"/fileUpload",
        data: {
          queteurId: queteurId,
          signedForms:
            [
              {queteur1Day        : vm.current.temporary_volunteer_form},
              {parentAuthorization: vm.current.parent_authorization_form}
            ]
        },
        method:'PUT'
      });

      upload.then(function success(response){
        $log.info('file ' + (response.config.data.file ? response.config.data.file.name:'undefined') + 'is uploaded successfully. Response: ' + response.data);
      },
      function error(error){
        $log.error(error);
      },
      function progress(evt){
        $log.info('progress: ' + parseInt(100.0 * evt.loaded / evt.total) + '% file :'+ (evt.config.data.file ? evt.config.data.file.name:'undefined'));
      });

    };


    vm.back=function()
    {
      window.history.back();
    };

    vm.save = function ()
    {
      //vm.uploadFiles();

      if (angular.isDefined(vm.current.id))
      {
        vm.current.$update(savedSuccessfully, errorWhileSaving);
      }
      else
      {
        vm.current.$save(savedSuccessfully, errorWhileSaving);
      }

    };

    vm.computeAge=function()
    {
      vm.current.age       = moment().diff(vm.current.birthdate, 'years');
    };


    /**
     * Set the queteur.id of the selected queteur in the model
     * */
    $scope.$watch('queteur.current.referent_volunteerQueteur', function(newValue/*, oldValue*/)
    {
      if(newValue !== null && typeof newValue === "object")
      {
        try
        {
          $log.info("queteurID set to "+newValue.id);
          $scope.queteur.current.referent_volunteer = newValue.id;
        }
        catch(exception)
        {
          $log.debug(exception);
        }
      }
    });

    /**
     * Function used while performing a manual search for a Queteur
     * @param queryString the search string (search is performed on first_name, last_name, nivol)
     * */
    vm.searchQueteur=function(queryString)
    {
      $log.info("Queteur : Manual Search for '"+queryString+"', active and benevoleOnly");
      return QueteurResource.query({"q":queryString, "active":1, "benevoleOnly":1}).$promise.then(function success(response)
      {
        return response.map(function success(queteur)
          {
            queteur.full_name= queteur.first_name+' '+queteur.last_name+' - '+queteur.nivol;
            return queteur;
          },
          function error(reason)
          {
            $log.debug("error while searching for queteur with query='"+queryString+"' with reason='"+reason+"'");
          });
      });
    };

    vm.createUser=function()
    {
      vm.current.user = new UserResource();
      vm.current.user.queteur_id = vm.current.id;
      vm.current.user.nivol      = vm.current.nivol;

      vm.current.user.$save(userSavedSuccessfully, errorWhileSaving);

    };

    vm.userSave=function()
    {
      var user = new UserResource();
      user.id     = vm.current.user.id;
      user.active = vm.current.user.active;
      user.role   = vm.current.user.role;

      user.$update(userSavedSuccessfully, errorWhileSaving);
    };

    vm.reinitPassword=function()
    {
      var user = new UserResource();
      user.id           = vm.current.user.id;
      user.queteur_id   = vm.current.id;
      user.nivol        = vm.current.nivol;

      user.$reInitPassword(userSavedSuccessfully, errorWhileSaving);
    };


    function userSavedSuccessfully(user)
    {
      vm.current.user=user;

      vm.savedSuccessfully=true;

      $timeout(function () { vm.savedSuccessfully=false; }, 5000);
    }


  }
})();

