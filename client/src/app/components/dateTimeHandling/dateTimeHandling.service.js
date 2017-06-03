/**
 * Created by tmanson on 03/05/2016.
 */

angular
  .module('client')
  .factory('DateTimeHandlingService', function($log, moment){

    var instance = {};

    /**
     * Server date are stored in UTC TimeZone. Javascript native date do not handle timezone.
     * For example, a paris TimeZone of 14h20 will be stored in DB as "12H20UTC"
     * Without any processing, the date are displayed in UTC and not in the local TimeZone
     * This code returns a javascript date in the local TimeZone.
     * */
    instance.handleServerDate=function(serverDate )
    {
      //date store in UTC + Timezone offset with Carbon on php side.
      //this parse the Carbon time without '000' ending in the UTC timezone, and then convert it to Europe/Paris (the value of the tronc_queteur.retour.timezone)
      var tempServerDate = moment.tz( serverDate.date.substring(0,serverDate.date.length -3 ),"YYYY-MM-DD HH:mm:ss.SSS", 'UTC');
      //Convert it to local TimeZone
      var finalDate= tempServerDate.clone().tz(serverDate.timezone).toDate();
      // don't understand why, but I've to add the offset to get the local timezone date as a string
      var stringVersion = tempServerDate .add(moment().utcOffset(), 'minutes').format("YYYY-MM-DD HH:mm:ss");

      return {
        dateInLocalTimeZone: finalDate,
        stringVersion: stringVersion
      };

    };

    return instance;
  });