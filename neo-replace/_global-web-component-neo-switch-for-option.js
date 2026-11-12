import { setOption } from "./_global--options.js";
import { neo__ } from "./_global--translation.js";
import Swal from "./_global-sweetalert2.js";

window.neoChangeSwitchForOptionNeoReplace = async (_this, onChangeFunction) => {
    try {
        if (onChangeFunction) { onChangeFunction({ checked: _this.checked, isSaved: false }); }
        await setOption(_this.getAttribute("data-switch-for-option-name"), _this.checked);
        if (onChangeFunction) { onChangeFunction({ checked: _this.checked, isSaved: true }); }
    } catch (error) {
        Swal.fire({ icon: "error", title: "Error", text: neo__("Could not update option:", "Konnte die Option nicht aktualisieren:") + " " + error.message, });
        if (onChangeFunction) { onChangeFunction({ checked: !_this.checked, isSaved: false }); }
        throw error;
    }
};
