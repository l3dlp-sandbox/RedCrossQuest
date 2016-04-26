/**
 * Created by tmanson on 15/04/2016.
 */

(function() {
  'use strict';

  angular
    .module('client')
    .controller('QueteursController', QueteursController);

  /** @ngInject */
  function QueteursController(/*QueteurResource*/) {
    var vm = this;

    vm.list =
    [
      { id: 0, lastName: 'Wayne', firstName: 'Bruce', address: 'Gotham city', phone: '555-BATMAN' },
      { id: 1, lastName: 'Parker', firstName: 'Peter', address: 'New York', phone: '555-SPDRMN' },
      { id: 2, lastName: 'Storm', firstName: 'Jane', address: 'Baxter building, New York', phone: '555-INVGRL' },
      { id: 3, lastName: 'Richards', firstName: 'Red', address: 'Baxter building, New York', phone: '555-MRFANT' },
      { id: 4, lastName: 'Storm', firstName: 'Johnny', address: 'Baxter building, New York', phone: '555-TORCH' },
      { id: 5, lastName: 'Grimm', firstName: 'Benjamin', address: 'Baxter building, New York', phone: '555-THING' },
      { id: 6, lastName: 'Murdock', firstName: 'Matt', address: 'San Francisco', phone: '555-DARDVL' },
      { id: 7, lastName: 'Stark', firstName: 'Tony', address: 'Stark tower, New York', phone: '555-IRNMAN' }
    ];

    // QueteurResource.query();


  }
})();

