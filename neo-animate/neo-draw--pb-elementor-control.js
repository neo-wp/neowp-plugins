import { DomNodeHelper } from "./_global--dom-node-helper.js";
import { neo__ } from "./_global--translation.js";
import { neoLoadInterfaceFunc } from "./_global--interface.js";

function onElementorLoaded() {
    const isNeoDrawControl = (control) => control.el.classList.contains("elementor-control-image");
    const originalMediaControl = elementor.modules.controls.Media;
    const neodrawImgUrlControl = elementor.modules.controls.Media.extend({
        onReady() { return (async () => {
            if (!isNeoDrawControl(this)) { return; }

            let imgUrl = this.getControlValue()?.url || "";
            if (imgUrl.endsWith("/placeholder.png")) {
                imgUrl = "";
            }

            const hint = neo__("Everything as usual, additionally with neoDraw.", "Alles wie gewohnt, zusätzlich mit neoDraw.");

            this.ui.input.after('<div class="neo-draw--description">' + hint + "</div>");

            const onSave = async (details) => {
                const newImgUrl = details.imgUrl;
                const newImgId = details.imgId;

                this.setValue("id", newImgId);

                this.setValue("url", newImgUrl);
                this.render();

                this.el.querySelector(".elementor-control-media__preview img")?.setAttribute("src", newImgUrl + "?c=" + Date.now());

                const previewIframeBody = document.getElementById("elementor-preview-iframe")?.contentWindow?.document?.body;
                const newImgUrlWithoutQuery = newImgUrl.split("?")[0];
                const newImgUrlWithoutProtocol = newImgUrlWithoutQuery.replace("http://", "").replace("https://", "");
                previewIframeBody?.querySelectorAll("img")?.forEach(img => {
                    const currentImgUrlWithoutQuery = (img.getAttribute("src") ?? "").split("?")[0];
                    const currentImgUrlWithoutProtocol = currentImgUrlWithoutQuery.replace("http://", "").replace("https://", "");
                    if (!currentImgUrlWithoutQuery || !newImgUrlWithoutQuery.endsWith(currentImgUrlWithoutProtocol) && !currentImgUrlWithoutQuery.endsWith(newImgUrlWithoutProtocol)) { return; }
                    img.src = newImgUrl + "?c=" + Date.now();
                });
            };

            const imgUrlControlField = this.el.querySelector(".elementor-control-media__preview");
            imgUrlControlField.innerHTML = "";

            const imgForTransform = document.createElement("img");
            imgForTransform.setAttribute("src", imgUrl);
            imgUrlControlField.appendChild(imgForTransform);

            if (this.el.querySelector(".elementor-control-title").innerText === neo__("Choose Image", "Bild wählen")) {
                this.el.querySelector(".elementor-control-title").innerText = neo__("Choose Image or Diagram", "Bild oder Diagramm wählen");
            }

            const editorDialog = new (await neoLoadInterfaceFunc("neo-animate", "neo-draw--editor-dialog.js", "InterfaceEditorDialog20260826"))().imgUrl(imgUrl).on("save", onSave);
            imgUrlControlField.classList.add("neo-draw--img-editable");
            imgUrlControlField.classList.add("neo-draw--checkerboard-background");
            imgUrlControlField.append(new DomNodeHelper(`<button type="button">${imgUrl ? neo__("Edit", "Bearbeiten") : neo__("Create neoDraw", "neoDraw erstellen")}</button>`)
                .on("click", (event) => { event.stopPropagation(); editorDialog.open(); })
                .withClasses(imgUrl ? "neo-draw--edit-button" : "neo-draw--create-button", "neo-draw--button-elementor-colored", "elementor-button", "elementor-button-default")
                .getNode()
            );
        })(); },
        deleteImage(event) {
            if (!isNeoDrawControl(this)) { return originalMediaControl.prototype.deleteImage.call(this, event); }
            this.setValue({ url: "", id: "" });
            this.render();
            event.stopPropagation();
        },
        onMediaInputImageSizeChange() {
            if (!isNeoDrawControl(this)) { return originalMediaControl.prototype.onMediaInputImageSizeChange.call(this); }
            this.render();

            if ( ! this.model.get( 'has_sizes' ) ) {
                return;
            }

            const currentControlValue = this.getControlValue(),
                placeholder = this.getControlPlaceholder();
            const hasImage = ( '' !== currentControlValue?.id ),
                hasPlaceholder = placeholder?.id,
                hasValue = hasImage || hasPlaceholder;
            if ( ! hasValue ) {
                return;
            }

            const shouldUpdateFromPlaceholder = ( hasPlaceholder && ! hasImage );

            if ( shouldUpdateFromPlaceholder ) {
                this.setValue( {
                    ...placeholder,
                    size: currentControlValue.size,
                } );

                if ( this.model.get( 'responsive' ) ) {
                    this.renderWithChildren();
                } else {
                    this.applySavedValue();
                }

                this.onMediaInputImageSizeChange();

                return;
            }

            let imageURL;

            elementor.channels.editor.once( 'imagesManager:detailsReceived', ( data ) => {
                imageURL = data[ currentControlValue.id ]?.[ currentControlValue.size ];

                if ( imageURL ) {
                    currentControlValue.url = imageURL;
                    this.setValue( currentControlValue );
                }
            } );

            imageURL = elementor.imagesManager.getImageUrl( {
                id: currentControlValue.id,
                url: currentControlValue.url,
                size: currentControlValue.size,
            } );

            if ( imageURL ) {
                currentControlValue.url = imageURL;
                this.setValue( currentControlValue );
            }
        },
    });

    elementor.addControlView("media", neodrawImgUrlControl);
}

if (window.elementor) {
    onElementorLoaded();
} else {
    window.addEventListener('elementor/init', onElementorLoaded, { once: true });
}
