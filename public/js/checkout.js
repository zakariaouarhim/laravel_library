document.addEventListener("DOMContentLoaded", function () {
        const creditCardRadio = document.getElementById("creditCard");
        const cashOnDeliveryRadio = document.getElementById("cashOnDelivery");
        const creditCardInfo = document.getElementById("creditCardInfo");

        creditCardRadio.addEventListener("change", function () {
            if (this.checked) {
                creditCardInfo.style.display = "block";
            }
           
        });

        cashOnDeliveryRadio.addEventListener("change", function () {
            if (this.checked) {
                creditCardInfo.style.display = "none";
                
            }
        });
    });