(function() {
    "use strict";
    var LANG_KEY = "vibe4dock.lang";
    var DICT = {
        de: {
            docTitle: "Veronica - {vibe} - Vibe4Dock",
            planModeTitle: "Plan mode",
            planMode: "Plan",
            planOn: "Planmodus aktiviert – deine Nachrichten laufen jetzt im Plan-Agent.",
            planOff: "Planmodus beendet – GO: die Umsetzung läuft.",
            planGo: "Setze den besprochenen Plan jetzt um.",
            cmdNew: "Neuen Chat starten",
            cmdHelp: "Hilfe anzeigen",
            cmdClear: "Chat-Verlauf leeren",
            dateToday: "Heute",
            dateYesterday: "Gestern",
            days: [ "Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag" ],
            dialogConfirm: "Bestätigen",
            dialogCancel: "Abbrechen",
            dialogNotice: "Hinweis",
            adminEmpty: "Noch keine Nutzer vorhanden.",
            adminDelete: "Löschen",
            pinTooShort: "Die PIN muss mindestens 4 Zeichen lang sein.",
            adminKeepOne: "Mindestens ein Admin muss erhalten bleiben.",
            pinChangeTitle: "PIN ändern",
            newPinFor: 'Neue PIN für "{alias}":',
            newPinPh: "Neue PIN (mind. 4 Zeichen)",
            save: "Speichern",
            selfDelete: "Du kannst deinen eigenen Account hier nicht löschen.",
            deleteUserMsg: 'Nutzer "{alias}" wirklich löschen?',
            deleteUserTitle: "Nutzer löschen",
            aliasInvalid: "Der Alias muss aus 2–3 Zeichen bestehen (Buchstaben, Zahlen, - und _).",
            aliasTaken: 'Der Alias "{alias}" ist bereits vergeben.',
            regDisabled: "Die Registrierung ist deaktiviert. Bitte einen Admin, dir einen Account anzulegen.",
            aliasTakenLogin: 'Der Alias "{alias}" ist bereits vergeben. Melde dich stattdessen an.',
            pinMismatch: "Die beiden PINs stimmen nicht überein.",
            noAccount: 'Kein Account für Alias "{alias}". Erstelle zuerst einen Account.',
            wrongPin: "Falsche PIN. Bitte erneut versuchen.",
            subLogin: "Melde dich mit deinem Alias an.",
            subRegister: "Wähle einen Alias (2–3 Zeichen) und eine PIN.",
            register: "Account erstellen",
            login: "Anmelden",
            toRegister: "Neu hier? Account erstellen",
            toLogin: "Bereits registriert? Anmelden",
            logoutConfirm: "Veronica schreibt gerade. Trotzdem abmelden?",
            logoutTitle: "Abmelden",
            sessionNew: "Neuer Chat",
            noMessages: "Noch keine Nachrichten",
            exportNone: "Dieser Chat hat keine Nachrichten zum Exportieren.",
            exportTitle: "Veronica Chat-Export",
            exportAt: "Exportiert: ",
            chatDeleteNone: "Kein aktiver Chat zum Löschen. Starte zuerst einen Chat.",
            chatDeleteAsk: "Diesen Chat wirklich löschen?",
            chatDeleteAsk2: "Chat löschen?",
            chatDeleteTitle: "Chat löschen",
            noActiveChat: "Kein aktiver Chat. Starte zuerst einen Chat.",
            addNone: "Keine weiteren Nutzer vorhanden. Neue Nutzer legt die Admin-Verwaltung an.",
            addMsg: "Person zum Chat hinzufügen – daraus wird eine Gruppe.",
            addTitle: "Person hinzufügen",
            addOk: "Hinzufügen",
            removeNone: "Keine entfernbaren Mitglieder vorhanden. Der Chat-Ersteller kann nicht entfernt werden.",
            removeMsg: "Person aus dem Chat entfernen – sie sieht den Chat danach nicht mehr.",
            removeTitle: "Person entfernen",
            removeOk: "Entfernen",
            emptyTitle: "Hallo! Ich bin Veronica.",
            emptyBody: "Schreib mir einfach – ich helfe dir gerne weiter.",
            mockHello1: "Hallo! Schön, dass du da bist. Wie kann ich dir helfen?",
            mockHello2: "Hi! Ich bin Veronica. Was steht heute an?",
            mockHello3: "Hey! Bereit, wenn du es bist – frag mich einfach.",
            mockProject1: "Ich arbeite direkt im Projekt-Verzeichnis. Soll ich mir zuerst die Struktur anschauen?",
            mockProject2: "Gerne. Soll ich das im Projekt nachschauen oder direkt einen Vorschlag machen?",
            mockDefault2: "Verstehe. Soll ich das im Projekt nachschauen oder direkt eine Lösung vorschlagen?",
            mockBye: "Bis bald! Wir lesen uns.",
            mockAsk: "Klar! Beschreib mir kurz, was du brauchst – Code, Konfiguration oder eine Analyse?",
            mockHelp: "Ich bin Veronica, dein Assistent. Schreib mir einfach – ich helfe dir bei Fragen rund um dein Projekt. Commands: `/new` (neuer Chat), `/clear` (Verlauf leeren), `/help` (diese Hilfe).",
            mockHelp2: "Frag mich alles – ich antworte so konkret wie möglich. Mit `/new` startest du einen neuen Chat.",
            mockAck: "Das klingt spannend. Magst du mir mehr Kontext geben?",
            mockAsk2: "Gute Frage. Ein paar Details wären hilfreich – was genau funktioniert nicht?",
            mockOkay: "Alles klar. Soll ich einen Vorschlag machen oder willst du erst die aktuelle Lage sehen?",
            mockThanks: "Gern geschehen! Wenn du noch etwas brauchst, sag einfach Bescheid.",
            errPrefix: "Fehler: ",
            providerHint: "Hinweis: Ist ein Provider konfiguriert? In der Anwendungshell <code>opencode auth login</code> ausführen oder den Container neu aufbauen.",
            freeTierHint: "OpenCode-Free-Tier-Modelle funktionieren nur innerhalb der OpenCode-App. Bitte oben rechts im Modell-Menü ein anderes Modell wählen – oder den Admin bitten, einen Provider zu konfigurieren (OPENCODE_PROVIDER/OPENCODE_MODEL bzw. opencode auth login).",
            modelMissing: "Gespeichertes Modell \"{model}\" ist nicht mehr verfügbar – Auswahl wurde zurückgesetzt, es wird das Server-Standardmodell verwendet.",
            permPrefix: "Freigabe: ",
            permAction: "Aktion",
            permAllow: "Erlauben",
            permAlways: "Immer erlauben",
            permReject: "Ablehnen",
            stuckHead: "Veronica hat seit über einer Minute nicht mehr geantwortet. Falls der Server neu gestartet wurde (z. B. Container-Rebuild), ist die laufende Anfrage verloren gegangen.",
            stuckUnlock: "Chat freigeben",
            stuckWait: "Weiter warten",
            statusTyping: "schreibt…",
            statusOnline: "online",
            modelsNone: "Keine Modelle verfügbar.",
            modelsFav: "Favoriten",
            modelsRecent: "Recent",
            modelsRec: "Empfehlungen",
            modelsAll: "Modelle",
            noticeCleared: "Verlauf geleert.",
            noticeNew: "Neuer Chat gestartet.",
            noticeCmds: "Commands: /new (neuer Chat), /clear (leeren), /help (Hilfe)",
            cmdErr: "Command-Fehler: ",
            typeImage: "Bilder",
            typeVideo: "Videos",
            typeAudio: "Audio",
            typePdf: "PDF",
            typeDoc: "Dokumente",
            typeSheet: "Tabellen",
            typeArchive: "Archive",
            typeOther: "Dateien",
            helpSessionTitle: "Hilfe",
            voice: "Sprachnachricht",
            voiceNote: "(Sprachnachricht, ",
            question: "Frage",
            questionStep: "Frage {n} von {total}",
            ownAnswerPh: "Oder eigene Antwort tippen…",
            ownAnswer: "Eigene Antwort…",
            qmTitle: "Freigabe erforderlich",
            adminUsersTitle: "Nutzer verwalten",
            searchPh: "Chat suchen oder neuen starten",
            meLabel: "Angemeldet als",
            sidebarShowTitle: "Seitenmenü einblenden",
            sidebarHideTitle: "Seitenmenü ausblenden",
            modelTitle: "Modell wählen",
            chatActionsTitle: "Chat-Aktionen",
            menuAdd: "Person hinzufügen",
            menuRemove: "Person entfernen",
            menuExport: "Chat exportieren",
            menuDelete: "Chat löschen",
            typingLabel: "Veronica schreibt",
            closeTitle: "Schließen",
            regLabel: " Selbst-Registrierung erlauben",
            regHint: "Steuert, ob sich Besucher selbst einen Account erstellen dürfen. Deaktiviert: Nur Admins legen Accounts an.",
            adminAliasPh: "Alias (2–3 Zeichen)",
            adminPinPh: "PIN (mind. 4 Zeichen)",
            makeAdmin: " Als Admin anlegen",
            adminAdd: "Nutzer anlegen",
            attachTitle: "Datei anhängen",
            attachFileOption: "Datei auswählen",
            attachGalleryOption: "Fotos & Videos (Galerie)",
            promptPh: "Nachricht eingeben…",
            micTitle: "Sprachnachricht",
            sendTitle: "Senden",
            stopTitle: "Stop",
            loginAliasPh: "2–3 Zeichen, z. B. ab",
            loginPinPh: "mind. 4 Zeichen",
            loginPin2Label: "PIN wiederholen",
            rememberLogin: "Angemeldet bleiben",
            dropText: "Dateien hier ablegen zum Hochladen",
            recCancel: "Abbrechen"
        },
        en: {
            docTitle: "Veronica - {vibe} - Vibe4Dock",
            planModeTitle: "Plan mode",
            planMode: "Plan",
            planOn: "Plan mode enabled - your messages now run in the plan agent.",
            planOff: "Plan mode disabled - GO: implementation is starting.",
            planGo: "Implement the discussed plan now.",
            cmdNew: "Start a new chat",
            cmdHelp: "Show help",
            cmdClear: "Clear chat history",
            dateToday: "Today",
            dateYesterday: "Yesterday",
            days: [ "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday" ],
            dialogConfirm: "Confirm",
            dialogCancel: "Cancel",
            dialogNotice: "Notice",
            adminEmpty: "No users yet.",
            adminDelete: "Delete",
            pinTooShort: "The PIN must be at least 4 characters long.",
            adminKeepOne: "At least one admin must remain.",
            pinChangeTitle: "Change PIN",
            newPinFor: 'New PIN for "{alias}":',
            newPinPh: "New PIN (min. 4 characters)",
            save: "Save",
            selfDelete: "You cannot delete your own account here.",
            deleteUserMsg: 'Delete user "{alias}"?',
            deleteUserTitle: "Delete user",
            aliasInvalid: "The alias must be 2–3 characters (letters, digits, - and _).",
            aliasTaken: 'The alias "{alias}" is already taken.',
            regDisabled: "Registration is disabled. Please ask an admin to create an account for you.",
            aliasTakenLogin: 'The alias "{alias}" is already taken. Please log in instead.',
            pinMismatch: "The two PINs do not match.",
            noAccount: 'No account for alias "{alias}". Create one first.',
            wrongPin: "Wrong PIN. Please try again.",
            subLogin: "Log in with your alias.",
            subRegister: "Choose an alias (2–3 characters) and a PIN.",
            register: "Create account",
            login: "Log in",
            toRegister: "New here? Create an account",
            toLogin: "Already registered? Log in",
            logoutConfirm: "Veronica is still writing. Log out anyway?",
            logoutTitle: "Log out",
            sessionNew: "New chat",
            noMessages: "No messages yet",
            exportNone: "This chat has no messages to export.",
            exportTitle: "Veronica chat export",
            exportAt: "Exported: ",
            chatDeleteNone: "No active chat to delete. Start a chat first.",
            chatDeleteAsk: "Really delete this chat?",
            chatDeleteAsk2: "Delete chat?",
            chatDeleteTitle: "Delete chat",
            noActiveChat: "No active chat. Start a chat first.",
            addNone: "No more users available. New users are created via the admin panel.",
            addMsg: "Add a person to the chat - this will become a group.",
            addTitle: "Add person",
            addOk: "Add",
            removeNone: "No removable members. The chat creator cannot be removed.",
            removeMsg: "Remove the person from the chat - they will no longer see it.",
            removeTitle: "Remove person",
            removeOk: "Remove",
            emptyTitle: "Hello! I am Veronica.",
            emptyBody: "Just write to me - I am happy to help.",
            mockHello1: "Hello! Great to see you. How can I help?",
            mockHello2: "Hi! I am Veronica. What is on the agenda today?",
            mockHello3: "Hey! Ready when you are - just ask.",
            mockProject1: "I work directly in the project directory. Shall I look at the structure first?",
            mockProject2: "Sure. Should I look it up in the project or make a suggestion right away?",
            mockDefault2: "I see. Should I look it up in the project or propose a solution directly?",
            mockBye: "See you soon! Talk to you later.",
            mockAsk: "Sure! Briefly describe what you need - code, configuration or an analysis?",
            mockHelp: "I am Veronica, your assistant. Just write to me - I help with questions about your project. Commands: `/new` (new chat), `/clear` (clear history), `/help` (this help).",
            mockHelp2: "Ask me anything - I will answer as concretely as possible. Use `/new` to start a new chat.",
            mockAck: "Sounds interesting. Would you like to give me more context?",
            mockAsk2: "Good question. A few details would help - what exactly is not working?",
            mockOkay: "Alright. Should I make a suggestion, or do you want to see the current situation first?",
            mockThanks: "You are welcome! If you need anything else, just let me know.",
            errPrefix: "Error: ",
            providerHint: "Hint: Is a provider configured? Run <code>opencode auth login</code> in the application shell or rebuild the container.",
            freeTierHint: "OpenCode free-tier models only work inside the OpenCode app itself. Please pick a different model in the model menu (top right) – or ask the admin to configure a provider (OPENCODE_PROVIDER/OPENCODE_MODEL or opencode auth login).",
            modelMissing: "Saved model \"{model}\" is no longer available – selection was reset, the server default model will be used.",
            permPrefix: "Approval: ",
            permAction: "Action",
            permAllow: "Allow",
            permAlways: "Always allow",
            permReject: "Reject",
            stuckHead: "Veronica has not responded for over a minute. If the server was restarted (e.g. container rebuild), the running request was lost.",
            stuckUnlock: "Unlock chat",
            stuckWait: "Keep waiting",
            statusTyping: "typing…",
            statusOnline: "online",
            modelsNone: "No models available.",
            modelsFav: "Favorites",
            modelsRecent: "Recent",
            modelsRec: "Recommended",
            modelsAll: "Models",
            noticeCleared: "History cleared.",
            noticeNew: "New chat started.",
            noticeCmds: "Commands: /new (new chat), /clear (clear), /help (help)",
            cmdErr: "Command error: ",
            typeImage: "Images",
            typeVideo: "Videos",
            typeAudio: "Audio",
            typePdf: "PDF",
            typeDoc: "Documents",
            typeSheet: "Spreadsheets",
            typeArchive: "Archives",
            typeOther: "Files",
            helpSessionTitle: "Help",
            voice: "Voice message",
            voiceNote: "(Voice message, ",
            question: "Question",
            questionStep: "Question {n} of {total}",
            ownAnswerPh: "Or type your own answer…",
            ownAnswer: "Own answer…",
            qmTitle: "Approval required",
            adminUsersTitle: "Manage users",
            searchPh: "Search chats or start a new one",
            meLabel: "Logged in as",
            sidebarShowTitle: "Show sidebar",
            sidebarHideTitle: "Hide sidebar",
            modelTitle: "Select model",
            chatActionsTitle: "Chat actions",
            menuAdd: "Add person",
            menuRemove: "Remove person",
            menuExport: "Export chat",
            menuDelete: "Delete chat",
            typingLabel: "Veronica is typing",
            closeTitle: "Close",
            regLabel: " Allow self-registration",
            regHint: "Controls whether visitors may create their own account. Disabled: only admins create accounts.",
            adminAliasPh: "Alias (2–3 characters)",
            adminPinPh: "PIN (min. 4 characters)",
            makeAdmin: " Create as admin",
            adminAdd: "Create user",
            attachTitle: "Attach file",
            attachFileOption: "Choose file",
            attachGalleryOption: "Photos & videos (gallery)",
            promptPh: "Type a message…",
            micTitle: "Voice message",
            sendTitle: "Send",
            stopTitle: "Stop",
            loginAliasPh: "2–3 characters, e.g. ab",
            loginPinPh: "min. 4 characters",
            loginPin2Label: "Repeat PIN",
            rememberLogin: "Remember me",
            dropText: "Drop files here to upload",
            recCancel: "Cancel"
        }
    };
    window.I18N = function() {
        var lang = "en";
        var langs = Object.keys(DICT);
        function detect() {
            try {
                var saved = localStorage.getItem(LANG_KEY);
                if (saved && langs.indexOf(saved) !== -1) return saved;
            } catch (e) {}
            var nav = (navigator.languages && navigator.languages[0] || navigator.language || (navigator.userLanguage || "en")).toLowerCase();
            for (var i = 0; i < langs.length; i++) {
                if (nav.indexOf(langs[i]) === 0) return langs[i];
            }
            return "en";
        }
        var VIBE_NAME = window.CHAT_CONFIG && window.CHAT_CONFIG.vibeName || "Vibe4Dock";
        function interpolate(s, vars) {
            s = s.replace(/\{vibe\}/g, VIBE_NAME);
            if (!vars) return s;
            return s.replace(/\{(\w+)\}/g, function(m, k) {
                return vars[k] !== undefined ? String(vars[k]) : m;
            });
        }
        function t(key, vars) {
            var entry = DICT[lang][key];
            if (entry === undefined) entry = DICT.en[key];
            if (entry === undefined) entry = key;
            return interpolate(entry, vars);
        }
        function raw(key) {
            var entry = DICT[lang][key];
            if (entry === undefined) entry = DICT.en[key];
            return entry;
        }
        function apply(root) {
            root = root || document;
            var nodes = root.querySelectorAll("[data-i18n]");
            for (var i = 0; i < nodes.length; i++) {
                nodes[i].textContent = t(nodes[i].getAttribute("data-i18n"));
            }
            nodes = root.querySelectorAll("[data-i18n-placeholder]");
            for (i = 0; i < nodes.length; i++) {
                nodes[i].placeholder = t(nodes[i].getAttribute("data-i18n-placeholder"));
            }
            nodes = root.querySelectorAll("[data-i18n-title]");
            for (i = 0; i < nodes.length; i++) {
                nodes[i].title = t(nodes[i].getAttribute("data-i18n-title"));
            }
            nodes = root.querySelectorAll("[data-i18n-html]");
            for (i = 0; i < nodes.length; i++) {
                nodes[i].innerHTML = t(nodes[i].getAttribute("data-i18n-html"));
            }
            var sw = root.querySelectorAll(".lang-switch");
            for (i = 0; i < sw.length; i++) {
                var btns = sw[i].querySelectorAll("button[data-lang]");
                for (var j = 0; j < btns.length; j++) {
                    var active = btns[j].getAttribute("data-lang") === lang;
                    btns[j].classList.toggle("active", active);
                }
            }
            document.documentElement.lang = lang;
        }
        function setLang(l) {
            if (langs.indexOf(l) === -1 || l === lang) return;
            lang = l;
            try {
                localStorage.setItem(LANG_KEY, l);
            } catch (e) {}
            apply();
            document.dispatchEvent(new CustomEvent("i18n:change", {
                detail: {
                    lang: lang
                }
            }));
        }
        lang = detect();
        document.addEventListener("click", function(e) {
            var btn = e.target && e.target.closest ? e.target.closest(".lang-switch button[data-lang]") : null;
            if (btn) setLang(btn.getAttribute("data-lang"));
        });
        return {
            t: t,
            raw: raw,
            apply: apply,
            setLang: setLang,
            lang: function() {
                return lang;
            }
        };
    }();
    window.t = function(key, vars) {
        return window.I18N.t(key, vars);
    };
    window.I18N.apply();
})();