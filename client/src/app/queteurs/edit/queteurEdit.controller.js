/**
 * Created by tmanson on 15/04/2016.
 */

(function() {
  'use strict';

  angular
    .module('redCrossQuestClient')
    .controller('QueteurEditController', QueteurEditController);

  /** @ngInject */
  function QueteurEditController($rootScope, $scope, $log, $routeParams, $location, $localStorage, $timeout,
                                 QueteurResource, UserResource, TroncQueteurResource, UniteLocaleResource,
                                 moment, DateTimeHandlingService)
  {
    var vm = this;

    var queteurId      = $routeParams.id;
    vm.isRegistration  = $routeParams.registration === "1";
    vm.currentUserRole = $localStorage.currentUser.roleId;
    vm.ulId            = $localStorage.currentUser.ulId;

    vm.rgpdVideoUrl    = $localStorage.guiSettings.RGPDVideo;
    vm.nivolOld        = [];

    vm.youngestBirthDate=moment().subtract(1  ,'years').toDate();
    vm.oldestBirthDate  =moment().subtract(100,'years').toDate();

    vm.pointQueteHash   = $localStorage.pointsQueteHash;



    vm.typeBenevoleList=[
      {id:1,label:'Action Sociale'                        },
      {id:2,label:'Secours'                               },
      {id:3,label:'Bénévole d\'un Jour'                   },
      {id:4,label:'Ancien Bénévole, Inactif ou Adhérent'  },
      {id:5,label:'Commerçant'                            },
      {id:6,label:'Spécial'                               }
    ];

    vm.roleList=[
      {id:1,label:'Lecture Seule' },
      {id:2,label:'Opérateur'     },
      {id:3,label:'Compteur'      },
      {id:4,label:'Administrateur'}
    ];

    vm.roleDesc=[];
    vm.roleDesc[1] = 'Consultation des quêteurs et le graphique public';
    vm.roleDesc[2] = 'Liste/Ajout/Update des quêteurs, préparation/départ/retour des troncs, graphique opérationnel';
    vm.roleDesc[3] = 'Opérateur + Comptage des troncs et tous les graphiques opérationnel et compteur';
    vm.roleDesc[4] = 'Compteur + administration des utilisateurs et paramétrage de RCQ pour l\'UL et accès à tous les graphiques';

    vm.createNewQueteur=function()
    {
      vm.current          = new QueteurResource();
      vm.current.ul_id    = vm.ulId;
      vm.current.ul_name  = $localStorage.currentUser.ulName;
      vm.current.active   = true;
      vm.isRegistration   = false;

      vm.current.unAnonymizeConfirmed     = false;
      vm.current.anonymizeAskConfirmation = false;
      vm.current.doAnonymizeButtonDisabled= false;


    };

    vm.handleDate = function (theDate)
    {
      if(angular.isDefined(theDate))
        return DateTimeHandlingService.handleServerDate(theDate).stringVersion;
      else
        return "";
    };

    vm.handleQueteur = function(queteur)
    {

      try
      {
        vm.current = queteur;

        vm.current.exportDataAskConfirmation=false;


        if(vm.isRegistration)
        {
          $rootScope.$emit('title-updated', 'Validation de l\'inscription d\'un quêteur - '+vm.current.id+' - '+vm.current.first_name+' '+vm.current.last_name);
        }
        else
        {
          $rootScope.$emit('title-updated', 'Edition du quêteur - '+vm.current.id+' - '+vm.current.first_name+' '+vm.current.last_name);
        }


        vm.current.created = vm.handleDate(vm.current.created);
        vm.current.updated = vm.handleDate(vm.current.updated);

        vm.current.ul_id=vm.current.ul_id+"";// otherwise the generated select is ? number(348) ?

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
            vm.current.mobile = parseInt(vm.current.mobile.charAt(0) ==='+' ? vm.current.mobile.slice(1):vm.current.mobile);
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
          var birthdate = vm.current.birthdate.substr(0,10);

          if(birthdate === '1922-12-22' || birthdate === '2007-07-07')
          {
            vm.current.birthdate = null;
          }
          else
          {
            vm.current.birthdate = moment( queteur.birthdate.substring(0, queteur.birthdate.length -16 ),"YYYY-MM-DD").toDate();
            vm.computeAge();
          }
        }

        vm.current.anonymization_date = vm.handleDate(vm.current.anonymization_date);

        if(angular.isDefined(vm.current.user) && vm.current.user != null)
        {
          vm.current.user.created = vm.handleDate(vm.current.user.created);
          vm.current.user.updated = vm.handleDate(vm.current.user.updated);
          vm.current.user.last_failure_login_date    = vm.handleDate(vm.current.user.last_failure_login_date);
          vm.current.user.last_successful_login_date = vm.handleDate(vm.current.user.last_successful_login_date);
        }

        if(!vm.isRegistration)
        {
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
                  data[i].duration = moment.duration(moment(data[i].retour).diff(moment(data[i].depart))).asMinutes();
                }
              }

              vm.current.troncs_queteur  = data;
            },
            function error(error)
            {
              $log.error(error);
            }
          );
        }
        else
        {
          //to help the operator to see if the registration is not a duplicate of an existing user.
          //in RedQuest : it means attaching the authentication mode to the existing queteur instead of the registration

          if(vm.current.registration_approved == false && vm.current.reject_reason === '')
          {//preset to true the registration approval
            vm.current.registration_approved = true;
          }
          vm.searchSimilar();
        }
      }
      catch(ex)
      {
        $log.error(JSON.stringify(queteur));
        $log.error(JSON.stringify(ex));
      }
    };

// Load data or create new queteur (after function definition)
    if (angular.isDefined(queteurId))
    {
      if(vm.isRegistration)
      {
        QueteurResource.getQueteurRegistration({ 'id': queteurId }).$promise.then(vm.handleQueteur).catch(function(e){
          $log.error("error searching for QueteurRegistration", e);
        });
      }
      else
      {
        QueteurResource.get({ 'id': queteurId }).$promise.then(vm.handleQueteur).catch(function(e){
          $log.error("error searching for Queteur", e);
        });
      }
    }
    else
    {
      vm.createNewQueteur();
      $rootScope.$emit('title-updated', 'Création d\'un nouveau quêtêur');
    }

    vm.savedSuccessfullyFunction=function(response)
    {
      vm.current.saveInProgress=false;
      if(typeof response.queteurId ==='number')
      {
        if(response.queteurId > 0)
        {
          vm.goToQueteur(response.queteurId);
        }
        else
        {
          delete $location.$$search.registration;
          $location.path('/queteurs/pendingValidation').replace();
        }
      }

      vm.savedSuccessfully                = true;
      vm.current.anonymization_token      = null;
      vm.current.anonymization_date       = null;
      vm.current.unAnonymizeConfirmed     = false;
      vm.current.anonymizeAskConfirmation = false;
      vm.current.doAnonymizeButtonDisabled= false;

      vm.current.birthdate = moment(vm.current.birthdate).toDate();

      $timeout(function () { vm.savedSuccessfully=false; }, 5000);
      $scope.queteurForm.secteur.$setPristine();
    };

    vm.errorWhileSavingFunction=function(error)
    {
      vm.current.saveInProgress=false;
      vm.errorWhileSaving=true;
      vm.errorWhileSavingDetails=error;
    };

    vm.back=function()
    {
      window.history.back();
    };

    vm.save = function ()
    {
      if(angular.isDefined(vm.current.anonymization_token) &&
          vm.current.anonymization_token !=  null &&
          vm.current.anonymization_token !== ""   &&
         !vm.current.unAnonymizeConfirmed)
      {
        vm.current.unanonymizeAskConfirmation=true;
      }
      else
      {
        if(typeof vm.current.id !== 'undefined' && vm.current.id === vm.current.referent_volunteer)
        {
          vm.current.referent_volunteerQueteur=null;
          vm.current.referent_volunteer=null;
          vm.errorWhileSaving=true;
          vm.errorWhileSavingDetails="Arrêtez de jouer aux apprentis sorciers... merci.";

          return;
        }
        vm.current.saveInProgress=true;
        if (!vm.isRegistration && angular.isDefined(vm.current.id) && vm.current.id != null)
        {//WARNING : le 9 janvier (heure d'hiver), coté javascript la date envoyé est le jour d'avant à 23h
          // le fix ci dessous, envoie la date en string, qui est vue comme une date venant de la DB pour Entity.php

          vm.current.birthdate = new Date(DateTimeHandlingService.handleDateWithoutTime(vm.current.birthdate));

          vm.current.$update(vm.savedSuccessfullyFunction, vm.errorWhileSavingFunction);
        }
        else
        {
          vm.current.ul_name=''; // ul_name : Id - name - postal code - city : too long for server side validation
          if(vm.isRegistration)
          {
            //otherwise Angular make a post on queteur/id, which match the update queteur( put))
            vm.current.registration_id = vm.current.id;
            delete vm.current.id;
            vm.current.$approveQueteurRegistration(vm.savedSuccessfullyFunction, vm.errorWhileSavingFunction);
          }
          else
          {
            vm.current.$save(vm.savedSuccessfullyFunction, vm.errorWhileSavingFunction);
          }

        }
      }
    };

    vm.associateRegistrationWithExistingQueteur=function(queteurId)
    {
      //store the registration id in the correct variable
      vm.current.registration_id  = vm.current.id;
      //store the queteur.id of the queteur we want to associate the registration with
      vm.current.id               = queteurId;

      vm.current.$associateRegistrationWithExistingQueteur(function success(response)
                                                           {
                                                             vm.goToQueteur(response.queteurId);
                                                           },
                                                           vm.errorWhileSavingFunction);
    };

    vm.confirmUnAnonymize=function()
    {
      vm.current.unAnonymizeConfirmed = true;
      vm.save();
    };



    vm.computeAge=function()
    {
      vm.current.age       = moment().diff(vm.current.birthdate, 'years');
    };



    /* SEARCH REFERENT */

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
      return QueteurResource.query({"q":queryString, "active":1, "benevoleOnly":1}).$promise.then(
        function success(response)
        {
          return response.rows.map(
            function success(queteur)
            {
              queteur.full_name= queteur.first_name+' '+queteur.last_name+' - '+queteur.nivol;
              return queteur;
            },
            function error(reason)
            {
              $log.debug("error while searching for queteur with query='"+queryString+"' with reason='"+reason+"'");
            });
        },
        function error(reason)
        {
          $log.debug("error while searching for queteur with query='"+queryString+"' with reason='"+reason+"'");
        });
    };
    /* END  SEARCH REFERENT */

    /* SEARCH UNITE LOCALE */
    /**
     * Function used while performing a manual search for an Unité Locale
     * @param queryString the search string (search is performed on name, postal code, city)
     * */
    vm.searchUL=function(queryString)
    {
      $log.info("UL : Manual Search for '"+queryString+"'");
      return UniteLocaleResource.query({"q":queryString}).$promise.then(function success(response)
      {
        return response.map(function(ul)
          {
            ul.full_name=  ul.id + ' - ' + ul.name+' - '+ul.postal_code+' - '+ul.city;
            return ul;
          },
          function error(reason)
          {
            $log.debug("error while searching for ul with query='"+queryString+"' with reason='"+reason+"'");
          });
      });
    };

    //This watch change on queteur variable to update the queteurId field
    $scope.$watch('queteur.current.ul_name', function(newValue/*, oldValue*/)
    {
      if(newValue !== null && typeof newValue !==  "string" && typeof newValue !== "undefined")
      {
        try
        {
          $scope.queteur.current.ul_id   = newValue.id;
          $scope.queteur.current.ul_name = newValue.full_name;
        }
        catch(exception)
        {
          $log.debug(exception);
        }
      }
    });

    /* END SEARCH UNITE LOCALE */

    vm.userSavedSuccessfully=function(user)
    {

      if(user.error)
      {
        return vm.errorWhileSavingUserFunction(user.error);
      }

      vm.current.user=user;
      vm.current.userSavedSuccessfully=true;
      vm.current.saveInProgress=false;
      $timeout(function () { vm.savedSuccessfully=false; }, 5000);
    };

    vm.errorWhileSavingUserFunction=function(error)
    {
      vm.current.saveInProgress             = false;
      vm.current.userErrorWhileSaving       = true;
      vm.current.userErrorWhileSavingDetails= error;
      vm.current.initPasswordEmailSent      = false;
      vm.current.reinitPasswordEmailSent    = false;
    };

    vm.createUser=function()
    {
      vm.current.user             = new UserResource();
      vm.current.user.queteur_id  = vm.current.id;
      vm.current.user.nivol       = vm.current.nivol;

      vm.current.saveInProgress             = true;
      vm.current.userSavedSuccessfully      = false;
      vm.current.userErrorWhileSaving       = false;
      vm.current.userErrorWhileSavingDetails= '';

      //assume that the operation will go successfull.
      //in the error function, it's set to false
      vm.current.initPasswordEmailSent = true;


      vm.current.user.$save(vm.userSavedSuccessfully, vm.errorWhileSavingUserFunction);
    };

    vm.userSave=function()
    {
      var user = new UserResource();
      user.id     = vm.current.user.id;
      user.active = vm.current.user.active;
      user.role   = vm.current.user.role;

      vm.current.saveInProgress             = true;
      vm.current.userSavedSuccessfully      = false;
      vm.current.userErrorWhileSaving       = false;
      vm.current.userErrorWhileSavingDetails= '';

      user.$update(vm.userSavedSuccessfully, vm.errorWhileSavingUserFunction);
    };

    vm.reinitPassword=function()
    {
      var user = new UserResource();
      user.id           = vm.current.user.id;
      user.queteur_id   = vm.current.id;
      user.nivol        = vm.current.nivol;

      vm.current.saveInProgress             = true;
      vm.current.userSavedSuccessfully      = false;
      vm.current.userErrorWhileSaving       = false;
      vm.current.userErrorWhileSavingDetails= '';

      vm.current.reinitPasswordEmailSent    = true;
      vm.current.exportDataAskConfirmation  = false;

      user.$reInitPassword(vm.userSavedSuccessfully, vm.errorWhileSavingUserFunction);
    };

    vm.searchSimilar=function()
    {
      if((!vm.current.id || vm.isRegistration) && (vm.current.first_name || vm.current.last_name || vm.current.nivol || vm.current.mobile || vm.current.email))
      {
        QueteurResource.searchSimilarQueteurs(
          { 'first_name': vm.current.first_name,
            'last_name': vm.current.last_name,
            'nivol': vm.current.nivol,
            'email': vm.current.email,
            'mobile': vm.current.mobile
          }
        ).$promise.then(function(queteurs)
        {
          vm.current.similarQueteurs = queteurs;
        }).catch(function(e){
          $log.error("error searching for searchSimilarQueteur", e);
        });
      }
    };

    vm.goToQueteur=function(queteurId)
    {
      delete $location.$$search.registration;
      $location.path('/queteurs/edit/' + queteurId).replace();
    };






    vm.exportQueteurData=function()
    {
      vm.current.exportDataAskConfirmation=true;
    };

    vm.doExportQueteurData=function()
    {
      vm.current.doExportQueteurDataButtonDisabled=true;
      QueteurResource.exportData({"id":vm.current.id}).$promise.then(function(result){
        vm.current.exportDataResult = result;
        vm.current.doExportQueteurDataButtonDisabled=false;
      }, vm.errorWhileSavingFunction);
    };



    vm.anonymize=function()
    {
      vm.current.anonymizeAskConfirmation=true;
    };

    vm.doAnonymize=function()
    {
      vm.current.doAnonymizeButtonDisabled=true;
      vm.current.$anonymize(vm.handleQueteur, vm.errorWhileSavingFunction);
    };

    vm.isEqual = function(string1, string2) {
      string1 = (string1 != null) ? String(string1).toUpperCase() : '';
      string2 = (string2 != null) ? String(string2).toUpperCase() : '';

      return string1 === string2;
    };




  }
})();

