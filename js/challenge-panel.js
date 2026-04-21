/**
 * Challenge panel shared logic for node editor.
 * Used by pages/admin/new-route.php and pages/admin/edit-route.php.
 */

let challengeCurrentType = 'none';
const challengePanelI18n = window.challengePanelTranslations || {
    optionLabel: 'Vaihtoehto :number',
    deleteTitle: 'Poista'
};

function formatChallengePanelMessage(template, params) {
    return Object.keys(params).reduce(function(message, key) {
        return message.replace(':' + key, params[key]);
    }, template);
}

function setChallengeType(type) {
    challengeCurrentType = type;

    document.getElementById('challengeMCFields').style.display = type === 'multiple_choice' ? 'block' : 'none';
    document.getElementById('challengeTextFields').style.display = type === 'text' ? 'block' : 'none';

    document.getElementById('challengeTypeNone').className = type === 'none'
        ? 'btn btn-sm btn-warning' : 'btn btn-sm btn-outline-warning';
    document.getElementById('challengeTypeMC').className = type === 'multiple_choice'
        ? 'btn btn-sm btn-warning' : 'btn btn-sm btn-outline-warning';
    document.getElementById('challengeTypeText').className = type === 'text'
        ? 'btn btn-sm btn-warning' : 'btn btn-sm btn-outline-warning';
}

function addChallengeOption(text) {
    const container = document.getElementById('challengeOptions');
    if (container.children.length >= 4) return;

    const optIndex = container.children.length;

    const row = document.createElement('div');
    row.className = 'd-flex align-items-center gap-2 mb-2';
    row.dataset.optIndex = optIndex;

    const radio = document.createElement('input');
    radio.type = 'radio';
    radio.name = 'challengeCorrect';
    radio.value = optIndex;
    radio.className = 'form-check-input flex-shrink-0';

    const textInput = document.createElement('input');
    textInput.type = 'text';
    textInput.className = 'form-control form-control-sm';
    textInput.placeholder = formatChallengePanelMessage(challengePanelI18n.optionLabel, { number: optIndex + 1 });
    textInput.value = text || '';

    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'btn btn-sm btn-outline-danger flex-shrink-0';
    removeBtn.title = challengePanelI18n.deleteTitle;
    removeBtn.textContent = '✕';
    removeBtn.onclick = function() { removeChallengeOption(this); };

    row.appendChild(radio);
    row.appendChild(textInput);
    row.appendChild(removeBtn);
    container.appendChild(row);

    document.getElementById('addOptionBtn').style.display = container.children.length >= 4 ? 'none' : '';
    updateChallengeRemoveButtons();
}

function removeChallengeOption(btn) {
    const container = document.getElementById('challengeOptions');
    if (container.children.length <= 2) return;

    btn.closest('[data-opt-index]').remove();

    // Re-index remaining rows
    Array.from(container.children).forEach(function(row, i) {
        row.dataset.optIndex = i;
        row.querySelector('input[type="radio"]').value = i;
        row.querySelector('input[type="text"]').placeholder = formatChallengePanelMessage(challengePanelI18n.optionLabel, { number: i + 1 });
    });

    document.getElementById('addOptionBtn').style.display = '';
    updateChallengeRemoveButtons();
}

function updateChallengeRemoveButtons() {
    const container = document.getElementById('challengeOptions');
    const tooFew = container.children.length <= 2;
    container.querySelectorAll('button').forEach(function(btn) {
        btn.disabled = tooFew;
    });
}

function getChallengeData() {
    if (challengeCurrentType === 'none') return null;

    if (challengeCurrentType === 'multiple_choice') {
        const question = document.getElementById('challengeQuestion').value.trim();
        const container = document.getElementById('challengeOptions');
        const options = Array.from(container.children).map(function(row) {
            return row.querySelector('input[type="text"]').value.trim();
        });
        if (!question || options.some(function(o) { return !o; })) return null;
        const checkedRadio = document.querySelector('input[name="challengeCorrect"]:checked');
        const correct_index = checkedRadio ? parseInt(checkedRadio.value, 10) : 0;
        return { type: 'multiple_choice', question: question, options: options, correct_index: correct_index };
    }

    if (challengeCurrentType === 'text') {
        const question = document.getElementById('challengeTextQuestion').value.trim();
        const answer = document.getElementById('challengeTextAnswer').value.trim();
        if (!question || !answer) return null;
        return { type: 'text', question: question, answer: answer };
    }

    return null;
}

function setChallengeData(data) {
    resetChallengePanel();
    if (!data) return;

    setChallengeType(data.type);

    if (data.type === 'multiple_choice') {
        document.getElementById('challengeQuestion').value = data.question || '';
        const opts = (data.options && data.options.length >= 2) ? data.options : ['', ''];
        opts.forEach(function(opt) { addChallengeOption(opt); });
        const radios = document.querySelectorAll('input[name="challengeCorrect"]');
        const correctIdx = data.correct_index || 0;
        if (radios[correctIdx]) radios[correctIdx].checked = true;
    }

    if (data.type === 'text') {
        document.getElementById('challengeTextQuestion').value = data.question || '';
        document.getElementById('challengeTextAnswer').value = data.answer || '';
    }
}

function resetChallengePanel() {
    document.getElementById('challengeQuestion').value = '';
    document.getElementById('challengeOptions').innerHTML = '';
    document.getElementById('challengeTextQuestion').value = '';
    document.getElementById('challengeTextAnswer').value = '';
    document.getElementById('addOptionBtn').style.display = '';
    setChallengeType('none');
}
