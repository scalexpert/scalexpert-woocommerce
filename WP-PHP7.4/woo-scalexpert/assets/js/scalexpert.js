/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./js/components/account.js":
/*!**********************************!*\
  !*** ./js/components/account.js ***!
  \**********************************/
/***/ (() => {

let $;
document.addEventListener("DOMContentLoaded", function() {
  $ = jQuery;
  let btnNeworder = "#sg_newOrder #newBasket";
  if ($(btnNeworder).length) {
    createOverlay();
    addEventOnButtonReOrder(btnNeworder);
  }
});
function addEventOnButtonReOrder(btnNeworder) {
  if ($(btnNeworder).length) {
    $(btnNeworder).off().on("click", function(e) {
      e.preventDefault();
      callAjax(btnNeworder);
      return;
    });
  }
}
function createOverlay() {
  let html = "";
  html += '<div id="sg_overlay"><div class="sg_loader"></div></div>';
  $("body").append(html);
}
function openOverlay() {
  $("#sg_overlay").attr("data-open", "true");
}
function closeOverlay() {
  $("#sg_overlay").attr("data-open", "false");
}
function callAjax(btnNeworder) {
  if ($(btnNeworder).length && typeof sg_recreateCart_object !== "undefined" && typeof sg_recreateCart_object.ajaxurl !== "undefined") {
    let idOrder = parseInt($(btnNeworder).attr("data-orderId"));
    if (!isNaN(idOrder)) {
      $.ajax({
        type: "POST",
        headers: { "cache-control": "no-cache" },
        url: sg_recreateCart_object.ajaxurl,
        async: true,
        cache: false,
        dataType: "html",
        data: {
          ajax: true,
          action: "sg_recreateCart",
          orderID: idOrder
        },
        beforeSend: openOverlay()
      }).success(function(response) {
        if (typeof response !== "undefined" && response) {
          successAjax();
        } else {
          errorAjax(btnNeworder);
        }
      }).error(function() {
        errorAjax(btnNeworder);
      });
    }
  }
}
function errorAjax(btnNeworder) {
  let txtError = "New order creation failed !";
  if ($(btnNeworder).attr("data-errorText")) {
    txtError = $(btnNeworder).attr("data-errorText");
  }
  closeOverlay();
  alert(txtError);
}
function successAjax() {
  $("#sg_newOrder").submit();
}


/***/ }),

/***/ "./js/components/modal.js":
/*!********************************!*\
  !*** ./js/components/modal.js ***!
  \********************************/
/***/ (() => {

let $;
document.addEventListener("DOMContentLoaded", function() {
  $ = jQuery;
  initModal();
  $("body").on("updated_checkout", function() {
    initModal();
  });
  $("body").on("updated_wc_div", function() {
    initModal();
  });
});
function initModal() {
  if ($('[data-modal="sep_openModal"]').length) {
    let $modal;
    let solutionSelector = '.sep-Simulations-solution [data-js="selectSolutionSimulation"]';
    addEventChangeSimulation(solutionSelector);
    $('[data-modal="sep_openModal"]').off().on("click", function(e) {
      e.preventDefault();
      if ($($(this)).length) {
        if ($($(this).attr("data-idmodal")).length) {
          $modal = $($(this).attr("data-idmodal"));
        } else if ($($(this).attr("href")).length) {
          $modal = $($(this).attr("href"));
        }
        if (typeof $modal !== "undefined" && $modal) {
          openModal($modal);
        }
      }
    });
  }
}
openModal = function openModal2($modal) {
  if (typeof $modal !== "undefined" && $modal.length) {
    $modal.show();
    eventCloseModal($modal);
  }
};
function eventCloseModal($modal) {
  if (typeof $modal !== "undefined" && $modal.length) {
    $modal.off().on("click", function(event) {
      if (typeof event !== "undefined" && $(event.target).length && $(event.target).attr("id") === $modal.attr("id")) {
        $modal.hide();
      }
    });
    if ($modal.find(".close").length) {
      $modal.find(".close").off().on("click", function() {
        $modal.hide();
      });
    }
  }
}
function addEventChangeSimulation(solutionSelector) {
  if ($(solutionSelector).length) {
    $(solutionSelector).each(function(i, elm) {
      if (typeof elm !== "undefined" && $(elm).length) {
        let idSolution = $(elm).attr("data-id");
        if (typeof idSolution !== "undefined" && idSolution) {
          let idGroupSolution = $(elm).attr("data-groupid");
          $(elm).off().on("click", function(e) {
            e.preventDefault();
            let idGroupSolutionSelect = '.sep-Simulations-groupSolution[data-id="' + idGroupSolution + '"]';
            $(idGroupSolutionSelect + " .sep-Simulations-solution").hide();
            $(idGroupSolutionSelect + ' .sep-Simulations-solution[data-id="' + idSolution + '"]').show();
            return;
          });
        }
      }
    });
  }
}


/***/ }),

/***/ "./js/components/payment.js":
/*!**********************************!*\
  !*** ./js/components/payment.js ***!
  \**********************************/
/***/ (() => {

let $;
document.addEventListener("DOMContentLoaded", function() {
  $ = jQuery;
  initPayments();
  $("body").on("updated_checkout", function() {
    initPayments();
  });
});
function initPayments() {
  if ($('.payment_methods input[type="radio"]').length) {
    changeRadio($('.payment_methods input[type="radio"]:checked'));
    addEventOnRadio();
  }
  checkboxCGV();
  addEventPaymentButton();
}
function addEventOnRadio() {
  $('.payment_methods input[type="radio"]').on("change", function(event) {
    if (typeof event !== "undefined" && event && $(event.target).length) {
      changeRadio($(event.target));
    }
  });
}
function changeRadio($radio) {
  if (typeof $radio !== "undefined" && $radio && $radio.length && $("#place_order").length) {
    if ($radio.attr("value") === "scalexpert") {
      $("#place_order").hide();
    } else {
      $("#place_order").show();
    }
  }
}
function checkboxCGV() {
  let $checkboxCGV = $('form.woocommerce-checkout input[type="checkbox"]#terms, form#order_review input[type="checkbox"]#terms');
  if ($checkboxCGV.length) {
    $checkboxCGV.on("change", function(elm) {
      changeCheckboxCGV(elm.target);
    });
  }
}
function changeCheckboxCGV(checkboxCGV2) {
  let $checkboxCGV = $(checkboxCGV2);
  let $paymentButtons = $(".payment_method_scalexpert .sep_financialSolution .sep_financialSolution-buttonPay");
  if ($paymentButtons.length) {
    $paymentButtons.attr("disabled", "").removeAttr("disabled").removeClass("disabled");
    if (!$checkboxCGV.prop("checked")) {
      $paymentButtons.attr("disabled", "disabled").addClass("disabled");
    }
  }
}
function addEventPaymentButton() {
  let $paymentButtons = $(".payment_method_scalexpert .sep_financialSolution .sep_financialSolution-buttonPay");
  let $inputSolutionCode = $('.payment_method_scalexpert input[name="solutionCode"]');
  if ($paymentButtons.length && $inputSolutionCode.length) {
    $paymentButtons.off().on("click", function() {
      $inputSolutionCode.val($(this).attr("data-solutioncode"));
      $("#place_order").submit();
    });
  }
}


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be in strict mode.
(() => {
"use strict";
var __webpack_exports__ = {};
/*!*********************!*\
  !*** ./js/front.js ***!
  \*********************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _components_modal__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./components/modal */ "./js/components/modal.js");
/* harmony import */ var _components_modal__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_components_modal__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _components_payment__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./components/payment */ "./js/components/payment.js");
/* harmony import */ var _components_payment__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_components_payment__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _components_account__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./components/account */ "./js/components/account.js");
/* harmony import */ var _components_account__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_components_account__WEBPACK_IMPORTED_MODULE_2__);




})();

// This entry need to be wrapped in an IIFE because it need to be in strict mode.
(() => {
"use strict";
/*!*************************!*\
  !*** ./scss/front.scss ***!
  \*************************/
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin

})();

/******/ })()
;
//# sourceMappingURL=scalexpert.js.map