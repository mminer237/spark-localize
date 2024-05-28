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
	document.querySelector("input[type=hidden][name=targetLanguage]").value = lang;
	document.querySelectorAll("input[type=text]").forEach(input => {
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

	let input = document.querySelector(`input[name="${key}"]`);
	if (input !== null) {
		input.placeholder = value;
	}
	else {
		input = document.querySelector(`input[name^="${key}."]`);
		if (input === null) {
			console.error(`No input found for key ${key}`);
		}
		else {
			/* Split key into parts */
			const parts = value.split(/(?<=[.?!]"?)\s+/);
			for (let i = 0; i < parts.length; i++) {
				const input = document.querySelector(`input[name="${key}.${i}"]`);
				if (input !== null) {
					input.placeholder = parts[i];
				}
				else {
					console.error(`No input found for key ${key}.${i}`);
				}
			}
		}
	}
}
