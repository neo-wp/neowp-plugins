import { neo__ } from "./_global--translation.js";
import { setState, setStateSetters, save, close, exportSvgAsString } from "./neo-draw--editor-communication.js";
import { FilenameDialog, SaveDialog } from "./neo-draw--editor-react-dialog-save.js";
import { SaveMultiUsageDialog } from "./neo-draw--editor-react-dialog-save-multi-usage.js";
import { ErrorDialog } from "./neo-draw--editor-react-dialog-error.js";
import { LibraryDialog } from "./neo-draw--editor-react-dialog-library.js";
import { pluginUrl } from "./_global-plugin-and-uploads-url.js";
import { jsVar, jsVarExists } from "./_global--enqueue-loader.js";
import { getQueryParam } from "./_global--url-helper.js";
import { isInterfaceFunctionErrorMessage, isModuleAvailable } from "./_global--interface.js";
import Swal from "./_global-sweetalert2.js";
import { Dialog, NeoDrawSaveCloseButtons } from "./neo-draw--editor-react-dialog.js";

import { neoLoadInterfaceFunc } from "./_global--interface.js";

function NeoDrawEditorApp() {
  const excalidrawWrapperRef = React.useRef(null);
  const [excalidrawAPI, setExcalidrawAPI] = React.useState(null);

  const [imgUrl, setImgUrl] = React.useState("");

  const [insertedFromImgUrl, setInsertedFromImgUrl] = React.useState(null);

  const [saveLoading, setSaveLoading] = React.useState(false);

  const [saveDialogOpen, setSaveDialogOpen] = React.useState(false);

  const [saveDialogOnAnswer, setSaveDialogOnAnswer] = React.useState(null);

  const [filenameDialogOpen, setFilenameDialogOpen] = React.useState(false);

  const [filenameDialogDefaultFilename, setFilenameDialogDefaultFilename] = React.useState("neo-draw");
  const [filenameDialogOnAnswer, setFilenameDialogOnAnswer] = React.useState(null);

  const [saveMultiUsageDialogOpen, setSaveMultiUsageDialogOpen] = React.useState(false);

  const [saveMultiUsageDialogCountInCurrentPost, setSaveMultiUsageDialogCountInCurrentPost] = React.useState(0);

  const [saveMultiUsageDialogOnAnswer, setSaveMultiUsageDialogOnAnswer] = React.useState(null);

  const [errorMessage, setErrorMessage] = React.useState(null);

  const [closeEditorOnErrorOk, setCloseEditorOnErrorOk] = React.useState(false);

  const [isDirty, setIsDirty] = React.useState(false);

  const [lastChanges, setLastChanges] = React.useState(null);

  const [libraryDialogUrl, setLibraryDialogUrl] = React.useState(null);

  const [metadata, setMetadata] = React.useState(null);

  const [isAnimationDialogOpen, setIsAnimationDialogOpen] = React.useState(false);

  const [animationDialogSvg, setAnimationDialogSvg] = React.useState(null);

  const [animationDialogLoading, setAnimationDialogLoading] = React.useState(false);

  const [AnimationDialog, setAnimationDialog] = React.useState(null);

  const [motionPreviewElements, setMotionPreviewElements] = React.useState([]);

  const [MotionPreviewButton, setMotionPreviewButton] = React.useState(null);

  const [MotionStarterHint, setMotionStarterHint] = React.useState(null);

  React.useEffect(() => {
    setState({ excalidrawAPI, imgUrl, insertedFromImgUrl, isDirty, saveLoading, metadata });
  },          [excalidrawAPI, imgUrl, insertedFromImgUrl, isDirty, saveLoading, metadata]);
  React.useEffect(() => {
    const warnBeforeUnload = (event) => {
      if (!isDirty) { return; }
      event.preventDefault(); event.returnValue = "";
    };
    window.addEventListener("beforeunload", warnBeforeUnload);
    return () => window.removeEventListener("beforeunload", warnBeforeUnload);
  }, [isDirty]);
  React.useEffect(() => {
    (async () => {
      try {
        const loadedMotionStarterHint = await (await neoLoadInterfaceFunc("neo-animate", "neo-motion--draw-editor-preview.js", "interfaceNeoDrawMotionStarterHintSuppressErrorPopup20260616"))();
        setMotionStarterHint(() => loadedMotionStarterHint);
      } catch (e) {
        if (!isInterfaceFunctionErrorMessage(e.message)) { throw e; }
      }
    })();
    (async () => {
      try {
        if (!jsVar("neoDrawMotionIntegrationAvailable")) { return; }
        const loadedMotionPreviewButton = await (await neoLoadInterfaceFunc("neo-animate", "neo-motion--draw-editor-preview.js", "interfaceNeoDrawMotionPreviewButtonSuppressErrorPopup20260611"))();
        setMotionPreviewButton(() => loadedMotionPreviewButton);
      } catch (e) {
        if (!isInterfaceFunctionErrorMessage(e.message)) { throw e; }
      }
    })();
  }, []);

  window.neo__ ??= neo__;
  window.neoDrawGetImageUrl = () => imgUrl;
  window.neoDrawGetInsertedFromImageUrl = () => insertedFromImgUrl;

  const onChange = React.useCallback((elements, appState) => {
    setMotionPreviewElements(elements);

    const elementsForChangeDetection = structuredClone(elements);
    for (const element of elementsForChangeDetection) { delete element.baseline; delete element.version; delete element.versionNonce; delete element.updated; }
    const newChangesJSON = JSON.stringify({ elements: elementsForChangeDetection, appState: { viewBackgroundColor: appState.viewBackgroundColor } });

    if (newChangesJSON !== lastChanges) {
      if (lastChanges) { setIsDirty(true); }
      setLastChanges(newChangesJSON);
    }
  }, [lastChanges]);

  const onLinkOpen = React.useCallback((element, event) => {
    const link = element.link;
    if (link) {
      window.open(link, "_blank", "noopener");
      event.preventDefault();
    }
  }, []);

  const renderTopRightUI = React.useCallback(() => {
    return React.createElement(
      "div",
      {
        className: "neo-draw--top-right-ui",
        key: "top-right-ui"
      },
      React.createElement(NeoDrawSaveCloseButtons, { isDirty, saveLoading, onSave: () => save({ closeAfterSave: false, usageCheck: true }), onClose: (evt) => close(evt) }),

      isModuleAvailable("neo-feedback") && React.createElement(
        "button", {
          key: "feedback-button",
          className: "help-icon neo-draw--feedback-button",
          type: "button",
          onClick: async () => { await (await neoLoadInterfaceFunc("neo-animate", "neo-feedback.js", "interfaceOpenFeedbackDialog20260802"))(); },
          "aria-label": neo__("Give feedback", "Feedback geben"),
        },
        React.createElement("img", { src: pluginUrl() + "/_global-lucide-icons-thirdparty/message-square.svg", alt: "" })
      ),

      jsVar("neoDrawEditorSettingsPageUrl") && React.createElement(
        "a", {
          className: "excalidraw-button neo-draw--editor-button neo-draw--settings-button",
          href: jsVar("neoDrawEditorSettingsPageUrl"),
          key: "settings-button",
          target: "_blank",
          rel: "noopener noreferrer",
          "aria-label": neo__("Open settings", "Einstellungen öffnen"),
        },
        React.createElement(
          "img", {
            src: pluginUrl() + "/_global-lucide-icons-thirdparty/settings.svg",
            alt: "",
          }
        )
      ),

      React.createElement(
        "button", {
          className: "excalidraw-button neo-draw--editor-button neo-draw--icon-library-button",
          key: "library-button",
          onClick: () => excalidrawAPI.updateScene({
            appState: {
              openSidebar: { name: "default", tab: "library" }
            }
          }),
          title: neo__("Library", "Bibliothek"),
        },
        React.createElement(
          "img",
          {
            src: pluginUrl() + "/img/neo-draw--editor-library-icon.svg",
          }
        )
      ),

      React.createElement(
        "button",
        {
          className: "excalidraw-button neo-draw--editor-button neo-draw--animation-button " + (animationDialogLoading ? " neo-draw--animation-loading" : ""),
          key: "animation-button",
          title: neo__("Animation", "Animation"),
          onClick: async () => {
            if (!isModuleAvailable("neo-animate")) {
              const confirmResult = await Swal.fire({ icon: "info", title: neo__("Activate plugin", "Plugin aktivieren"), text: neo__("The plugin neoAnimate must be activated to use this feature.", "Das Plugin neoAnimate muss aktiviert werden, um diese Funktion zu nutzen."), showCancelButton: true, confirmButtonText: neo__("Activate plugin", "Plugin aktivieren"), cancelButtonText: neo__("Cancel", "Abbrechen") });
              if (!confirmResult.isConfirmed) { return; }
              if (!jsVar("neoDrawEditorSettingsPageUrl")) {
                setErrorMessage(neo__("neoSettings is not available. Please update all neoPlugins.", "neoSettings ist nicht verfügbar. Bitte aktualisiere alle neoPlugins."));
                setCloseEditorOnErrorOk(false);
                return;
              }
              const settingsPageUrl = new URL(jsVar("neoDrawEditorSettingsPageUrl"));
              settingsPageUrl.searchParams.set("neo-settings--open-section", "neo-animate");
              window.open(settingsPageUrl.toString(), "_blank", "noopener");
              return;
            }
            if (jsVarExists("neoDrawAnimationIntegrationAvailable") && !jsVar("neoDrawAnimationIntegrationAvailable")) { setErrorMessage(neo__("neoAnimate is not compatible with this neoDraw editor. Please update all neoPlugins.", "neoAnimate ist nicht mit diesem neoDraw-Editor kompatibel. Bitte aktualisiere alle neoPlugins.")); setCloseEditorOnErrorOk(false); return; }
            setAnimationDialogLoading(true);
            try {
              const loadedAnimationDialog = await (await neoLoadInterfaceFunc("neo-animate", "neo-animate--draw-editor-animation-dialog.js", "interfaceNeoDrawAnimationDialogSuppressErrorPopup20260611"))();
              setAnimationDialog(() => loadedAnimationDialog);
              setAnimationDialogSvg(await exportSvgAsString());
              setIsAnimationDialogOpen(true);
            } catch (e) {
              if (e.message.includes("neoAnimateEditorUrl") || e.message.includes("neoWP JS variables")) { setErrorMessage(neo__("neoAnimate is not compatible with this neoDraw editor. Please update all neoPlugins.", "neoAnimate ist nicht mit diesem neoDraw-Editor kompatibel. Bitte aktualisiere alle neoPlugins.")); setCloseEditorOnErrorOk(false); setAnimationDialogLoading(false); return; }
              setErrorMessage(e.message);
              setCloseEditorOnErrorOk(false);
            }
            setAnimationDialogLoading(false);
          },
        },
        React.createElement(
          "img",
          { src: pluginUrl() + "/img/neo-draw--editor-animate-icon.svg" }
        ),
        React.createElement(
          "div",
          {
            key: "animation-button-spinner",
            className: "neo-draw--spinner",
          }
        )
      ),
      MotionPreviewButton && React.createElement(MotionPreviewButton, {
        key: "motion-preview-button",
        elements: motionPreviewElements,
        exportSvgAsString,
        buttonClassName: "excalidraw-button neo-draw--editor-button neo-draw--motion-preview-button",
        buttonLoadingClassName: "neo-draw--motion-preview-loading",
        spinnerClassName: "neo-draw--spinner",
        previewPopupId: "neo-draw--motion-preview-popup",
        onError: (e) => { setErrorMessage(e.message); setCloseEditorOnErrorOk(false); },
      })
    );
  }, [isDirty, saveLoading, animationDialogLoading, MotionPreviewButton, motionPreviewElements, excalidrawAPI]);

  const libraryDialogOnClose = React.useCallback(() => {
    setLibraryDialogUrl(null);
  }, []);

  const saveDialogOnAnswerCallback = React.useCallback((answer) => {
    setSaveDialogOpen(false);
    saveDialogOnAnswer(answer);
  }, [saveDialogOnAnswer]);
  const filenameDialogOnAnswerCallback = React.useCallback((answer) => {
    setFilenameDialogOpen(false);
    filenameDialogOnAnswer(answer);
  }, [filenameDialogOnAnswer]);

  return React.createElement(
    "div",
    {
      className: "excalidraw-wrapper",
      ref: excalidrawWrapperRef
    },
    React.createElement(
      ExcalidrawLib.Excalidraw,
      {
        initialData: { elements: [], appState: { viewBackgroundColor: "#ffffff00", gridSize: 20 } },
        excalidrawAPI: (api) => {
          setExcalidrawAPI(api);
          setStateSetters({
            setImgUrl,
            setInsertedFromImgUrl,
            setSaveLoading,
            setSaveDialogOpen,
            setSaveDialogOnAnswer,
            setFilenameDialogOpen,
            setFilenameDialogDefaultFilename,
            setFilenameDialogOnAnswer,
            setSaveMultiUsageDialogOpen,
            setSaveMultiUsageDialogCountInCurrentPost,
            setSaveMultiUsageDialogOnAnswer,
            setErrorMessage,
            setCloseEditorOnErrorOk,
            setIsDirty,
            setLibraryDialogUrl,
            setMetadata,
          });
        },

        viewModeEnabled: false,
        handleKeyboardGlobally: true,
        autoFocus: true,
        langCode: jsVar("neoDrawEditorLanguageCode"),
        libraryReturnUrl: jsVar("neoDrawEditorIconLibraryUrl"),
        onChange,
        renderTopRightUI,
        onLinkOpen,
      },
      React.createElement(
        ExcalidrawLib.MainMenu,
        null,
        React.createElement(ExcalidrawLib.MainMenu.DefaultItems.SaveToActiveFile),
        React.createElement(ExcalidrawLib.MainMenu.DefaultItems.Export),
        React.createElement(ExcalidrawLib.MainMenu.Separator),
        React.createElement(ExcalidrawLib.MainMenu.DefaultItems.ClearCanvas),
        React.createElement(ExcalidrawLib.MainMenu.DefaultItems.Help),
        React.createElement(ExcalidrawLib.MainMenu.Separator),
        React.createElement(ExcalidrawLib.MainMenu.DefaultItems.ChangeCanvasBackground),
      ),
    ),
    MotionStarterHint && React.createElement(MotionStarterHint, {
      key: "motion-starter-hint",
      elements: motionPreviewElements,
      excalidrawAPI,
      onError: (e) => { setErrorMessage(e.message); setCloseEditorOnErrorOk(false); },
    }),
    libraryDialogUrl && React.createElement(LibraryDialog, {
      key: "library-dialog",
      url: libraryDialogUrl,
      onClose: libraryDialogOnClose
    }),

    isAnimationDialogOpen && AnimationDialog && React.createElement(AnimationDialog, {
      key: "animation-dialog",
      Dialog,
      SaveCloseButtons: NeoDrawSaveCloseButtons,
      svg: animationDialogSvg,
      animationMeta: metadata?.animation,
      onChange: async (newAnimationMeta) => {
        newAnimationMeta = await (await neoLoadInterfaceFunc("neo-animate", "neo-animate--draw-editor-animation-dialog.js", "interfaceAddNeoAnimatePluginVersionToAnimationMetaSuppressErrorPopup20260611"))(newAnimationMeta);
        if (JSON.stringify(metadata?.animation ?? null) !== JSON.stringify(newAnimationMeta)) { setIsDirty(true); }
        setMetadata({
          ...metadata,
          animation: newAnimationMeta,
        });
      },
      onRemoveAnimation: () => {
        const { animation, ...metadataWithoutAnimation } = metadata ?? {};
        if (metadata?.animation !== undefined) { setIsDirty(true); }
        setMetadata(metadataWithoutAnimation);
        setIsAnimationDialogOpen(false);
      },
      onClose: () => {
        setIsAnimationDialogOpen(false);
      },
      onSave: () => save({ closeAfterSave: false, usageCheck: true }),
      isDirty,
      saveLoading,
      closeTitle: "",
    }),
    saveDialogOpen && React.createElement(
      SaveDialog, {
      key: "save-dialog",
      onAnswer: saveDialogOnAnswerCallback,
    }),
    filenameDialogOpen && React.createElement(
      FilenameDialog, {
      key: "filename-dialog",
      defaultFilename: filenameDialogDefaultFilename,
      onAnswer: filenameDialogOnAnswerCallback,
    }),
    saveMultiUsageDialogOpen && React.createElement(
      SaveMultiUsageDialog, {
      key: "save-multi-usage-dialog",
      onAnswer: saveMultiUsageDialogOnAnswer,
      imgUrl: imgUrl,
      countInCurrentPost: saveMultiUsageDialogCountInCurrentPost,
    }),
    errorMessage && React.createElement(
      ErrorDialog, {
      key: "error-dialog",
      message: errorMessage,
      exportSvgAsString: exportSvgAsString,
      onClose: () => closeEditorOnErrorOk ? close() : setErrorMessage(null)
    })
  );
};

const excalidrawWrapper = document.getElementById("app");
ReactDOM.render(React.createElement(NeoDrawEditorApp), excalidrawWrapper);

if (isModuleAvailable("neo-animate") && !(getQueryParam(location.href, "img-url") === "base64" && getQueryParam(location.href, "default-filename") === "neo-motion.svg")) { (await neoLoadInterfaceFunc("neo-animate", "neo-tutorial.js", "interfaceShowTutorialArrowSuppressErrorPopup20260410"))(".neo-draw--animation-button", "left"); }
if (getQueryParam(location.href, "img-url") === "base64" && getQueryParam(location.href, "default-filename") === "neo-motion.svg") { (await neoLoadInterfaceFunc("neo-animate", "neo-tutorial.js", "interfaceShowTutorialArrowSuppressErrorPopup20260410"))(".neo-draw--motion-preview-button", "left", "preview"); }
