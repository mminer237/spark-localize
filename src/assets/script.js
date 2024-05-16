"use strict";

document.addEventListener("DOMContentLoaded", () => {
	const targetLanguageCodes = [];
	for (const targetLanguage in targetLanguages) {
		if (isNaN(targetLanguage)) {
			targetLanguageCodes.push(targetLanguage);
		}
		else {
			targetLanguageCodes.push(targetLanguages[targetLanguage]);
		}
	}
	targetLanguageCodes.sort();

	/* Replace target language selector with dropdown */
	const targetLanguageSelector = document.createElement("select");
	document.getElementById("targetLanguage").replaceWith(targetLanguageSelector);
	targetLanguageSelector.id = "targetLanguage";
	targetLanguageSelector.name = "targetLanguage";
	targetLanguageCodes.forEach(targetLanguageCode => {
		const option = document.createElement("option");
		option.value = targetLanguageCode;
		const languageNames = new Intl.DisplayNames([targetLanguageCode], { type: "language" });
		const languageName = languageNames.of(targetLanguageCode);
		option.innerText = languageName[0].toLocaleUpperCase(targetLanguageCode) + languageName.slice(1);
		targetLanguageSelector.appendChild(option);
	});
	targetLanguageSelector.addEventListener("change", updateLanguage);
	updateLanguage();
});

function updateLanguage() {
	const lang = document.getElementById("targetLanguage").value;

	/* Update language */
	document.querySelectorAll("input").forEach(input => {
		input.lang = lang;
		input.placeholder = "";
	});

	/* Fill in existing translations */
	if (targetLanguages[lang] === undefined)
		return;
	for (const [key, value] of Object.entries(targetLanguages[lang])) {
		updateEntry(key, value);
	}
}

function updateEntry(key, value) {
	if (typeof value === "object") {
		for (const [subkey, subvalue] of Object.entries(value)) {
			updateEntry(`${key}.${subkey}`, subvalue);
		}
		return;
	}

	const input = document.querySelector(`input[name="${key}"]`);
	input.placeholder = value;
}
