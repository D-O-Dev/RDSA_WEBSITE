import './bootstrap';


/* =========================================================
   RDSA — PARTICIPATION
   FRONTEND
   ========================================================= */


/* =========================================================
   CONFIGURATION TEMPORAIRE

   IMPORTANT :
   Ces valeurs sont temporaires pour le frontend.

   Plus tard Laravel les fournira depuis
   la configuration administrateur.
========================================================= */

const participationConfig = {

    edition: {
        id: null,
        name: 'RDSA 3',
        date: 'Samedi 08 août 2026'
    },

    previousEditions: [
        {
            id: 1,
            name: 'RDSA 1',
            date: '28 mars 2026'
        },
        {
            id: 2,
            name: 'RDSA 2',
            date: '08 août 2026'
        }
    ],

    pricing: {
        basePrice: 1000,
        allowAdditionalContribution: true
    },

    waveNumbers: [
        {
            id: 1,
            label: 'WAVE 01',
            number: '+225 07 XX XX XX XX',
            active: true
        },

        {
            id: 2,
            label: 'WAVE 02',
            number: '+225 01 XX XX XX XX',
            active: true
        },

        {
            id: 3,
            label: 'WAVE 03',
            number: '+225 05 XX XX XX XX',
            active: true
        }
    ],

    payment: {
        maxProofSize: 5 * 1024 * 1024,

        allowedProofTypes: [
            'image/jpeg',
            'image/png',
            'image/webp'
        ]
    }



};


/* =========================================================
   DONNÉES TEMPORAIRES DE PARTICIPATION
========================================================= */

let participationData = {

    name: '',
    phone: '',

    quantity: 1,

    // Historique
    hasPreviousParticipation: null,
    previousEditions: [],

    baseAmount: 0,
    amount: 0,

    // Paiement
    waveId: null,
    waveNumber: '',

    transactionReference: '',
    paymentAmount: 0,

    submissionDatetime: '',

    paymentProof: null,

    ticketReference: ''


};



/* =========================================================
   INITIALISATION
========================================================= */

document.addEventListener(
    'DOMContentLoaded',
    function () {

        initializeParticipation();

    }
);


/* =========================================================
   INITIALISATION PRINCIPALE
========================================================= */

function initializeParticipation() {

    /*
     * Configuration affichée
     */
    updateConfigurationDisplay();


    /*
     * Calcul initial
     */
    updateParticipationAmount();


    /*
     * Numéros Wave
     */
    renderWaveNumbers();


    /*
     * Quantité
     */
    initializeQuantityControls();


    /*
     * Montant
     */
    initializeAmountInput();


    /*
     * Preuve
     */
    initializeProofInput();


    /*
     * Champs
     */
    initializeInputValidation();

}


/* =========================================================
   AFFICHAGE CONFIGURATION
========================================================= */

function updateConfigurationDisplay() {

    const basePrice =
        participationConfig.pricing.basePrice;


    const basePriceFormatted =
        formatAmount(basePrice);


    const displayBasePrice =
        document.getElementById(
            'display_base_price'
        );


    if (displayBasePrice) {

        displayBasePrice.textContent =
            basePriceFormatted;

    }


    const unitPrice =
        document.getElementById(
            'calculation_unit_price'
        );


    if (unitPrice) {

        unitPrice.textContent =
            basePriceFormatted;

    }


    const eventName =
        document.getElementById(
            'participation_event_name'
        );


    if (eventName) {

        eventName.textContent =
            participationConfig.edition.name;

    }


    const eventDate =
        document.getElementById(
            'participation_event_date'
        );


    if (eventDate) {

        eventDate.textContent =
            participationConfig.edition.date;

    }


    /*
     * Contribution supplémentaire
     */
    const contributionContainer =
        document.getElementById(
            'additional-contribution-container'
        );


    if (
        contributionContainer &&
        !participationConfig.pricing
            .allowAdditionalContribution
    ) {

        contributionContainer.style.display =
            'none';

    }

}

/* ========================================
   HISTORIQUE DE PARTICIPATION
======================================== */

document.addEventListener('DOMContentLoaded', function () {

    const historyOptions =
        document.querySelectorAll('.history-option');

    const editionsContainer =
        document.getElementById(
            'previous-editions-container'
        );

    const editionsList =
        document.getElementById(
            'previous-editions-list'
        );


    /*
     * Génération dynamique des éditions
     */
    if (editionsList && participationConfig.previousEditions) {

        editionsList.innerHTML =
            participationConfig.previousEditions
                .map((edition) => {

                    return `
                        <label
                            class="edition-checkbox"
                        >

                            <input
                                type="checkbox"
                                value="${edition.id}"
                                data-edition-name="${edition.name}"
                            >

                            <span class="edition-checkbox-content">

                                <strong>
                                    ${edition.name}
                                </strong>

                                <small>
                                    ${edition.date}
                                </small>

                            </span>

                            <i class="bi bi-check-circle"></i>

                        </label>
                    `;

                })
                .join('');
    }


    /*
     * Choix Oui / Non
     */
    historyOptions.forEach((option) => {

        option.addEventListener(
            'click',
            function () {

                historyOptions.forEach((item) => {

                    item.classList.remove('active');

                    const icon =
                        item.querySelector('i');

                    if (icon) {
                        icon.className =
                            'bi bi-circle';
                    }

                });


                this.classList.add('active');

                const icon =
                    this.querySelector('i');

                if (icon) {
                    icon.className =
                        'bi bi-check-circle';
                }


                const answer =
                    this.dataset.history;


                if (answer === 'yes') {

                    participationData
                        .hasPreviousParticipation = true;

                    editionsContainer.hidden = false;

                } else {

                    participationData
                        .hasPreviousParticipation = false;

                    participationData
                        .previousEditions = [];

                    editionsContainer.hidden = true;


                    /*
                     * Réinitialiser les cases
                     */
                    editionsList
                        .querySelectorAll(
                            'input[type="checkbox"]'
                        )
                        .forEach((checkbox) => {

                            checkbox.checked = false;

                            checkbox
                                .closest('.edition-checkbox')
                                ?.classList.remove('active');

                        });

                }

            }
        );

    });


    /*
     * Sélection des éditions
     */
    if (editionsList) {

        editionsList.addEventListener(
            'change',
            function (event) {

                if (
                    !event.target.matches(
                        'input[type="checkbox"]'
                    )
                ) {
                    return;
                }


                const checkbox =
                    event.target;

                const edition =
                    participationConfig.previousEditions
                        .find(
                            item =>
                                String(item.id) ===
                                String(checkbox.value)
                        );


                if (!edition) {
                    return;
                }


                const editionElement =
                    checkbox.closest(
                        '.edition-checkbox'
                    );


                if (checkbox.checked) {

                    editionElement
                        ?.classList.add('active');

                } else {

                    editionElement
                        ?.classList.remove('active');

                }


                /*
                 * Mettre à jour les données
                 */
                participationData.previousEditions =
                    Array.from(
                        editionsList.querySelectorAll(
                            'input[type="checkbox"]:checked'
                        )
                    )
                        .map((input) => {

                            return Number(input.value);

                        });

            }
        );

    }

});


/* =========================================================
   FORMAT MONTANT
========================================================= */

function formatAmount(amount) {

    return Number(amount || 0)
        .toLocaleString(
            'fr-FR'
        );

}


/* =========================================================
   CALCUL DU MONTANT
========================================================= */

function calculateBaseAmount() {

    const quantity =
        Number(
            document.getElementById(
                'participant_quantity'
            )?.value || 1
        );


    const basePrice =
        Number(
            participationConfig.pricing.basePrice
        );


    return quantity * basePrice;

}


/* =========================================================
   MISE À JOUR DU MONTANT
========================================================= */

function updateParticipationAmount() {

    const quantityInput =
        document.getElementById(
            'participant_quantity'
        );


    if (!quantityInput) {
        return;
    }


    const quantity =
        Math.max(
            1,
            Number(quantityInput.value || 1)
        );


    const baseAmount =
        calculateBaseAmount();


    participationData.quantity =
        quantity;

    participationData.baseAmount =
        baseAmount;


    /*
     * Si le montant actuel est inférieur
     * au nouveau minimum, on le remonte.
     */
    const amountInput =
        document.getElementById(
            'participant_amount'
        );


    let amount =
        Number(
            amountInput?.value || baseAmount
        );


    if (
        !amount ||
        amount < baseAmount
    ) {

        amount =
            baseAmount;

        if (amountInput) {

            amountInput.value =
                amount;

        }

    }


    participationData.amount =
        amount;


    /*
     * Affichage calcul
     */
    const calculationQuantity =
        document.getElementById(
            'calculation_quantity'
        );


    if (calculationQuantity) {

        calculationQuantity.textContent =
            quantity;

    }


    const calculationBaseAmount =
        document.getElementById(
            'calculation_base_amount'
        );


    if (calculationBaseAmount) {

        calculationBaseAmount.textContent =
            formatAmount(baseAmount);

    }


    const finalAmount =
        document.getElementById(
            'final_amount'
        );


    if (finalAmount) {

        finalAmount.textContent =
            formatAmount(amount);

    }


    const waveAmount =
        document.getElementById(
            'wave_amount'
        );


    if (waveAmount) {

        waveAmount.textContent =
            formatAmount(amount);

    }


    const paymentAmount =
        document.getElementById(
            'payment_amount'
        );


    if (paymentAmount) {

        paymentAmount.value =
            amount;

    }


    const reviewTotal =
        document.getElementById(
            'review_total'
        );


    if (reviewTotal) {

        reviewTotal.textContent =
            formatAmount(amount);

    }

}


/* =========================================================
   QUANTITÉ
========================================================= */

function initializeQuantityControls() {

    const input =
        document.getElementById(
            'participant_quantity'
        );


    const minus =
        document.getElementById(
            'quantity_minus'
        );


    const plus =
        document.getElementById(
            'quantity_plus'
        );


    if (!input) {
        return;
    }


    minus?.addEventListener(
        'click',
        function () {

            let quantity =
                Number(input.value || 1);


            quantity =
                Math.max(
                    1,
                    quantity - 1
                );


            input.value =
                quantity;


            updateParticipationAmount();

        }
    );


    plus?.addEventListener(
        'click',
        function () {

            let quantity =
                Number(input.value || 1);


            quantity += 1;


            input.value =
                quantity;


            updateParticipationAmount();

        }
    );

}


/* =========================================================
   MONTANT PERSONNALISÉ
========================================================= */

function initializeAmountInput() {

    const amountInput =
        document.getElementById(
            'participant_amount'
        );


    if (!amountInput) {
        return;
    }


    amountInput.addEventListener(
        'input',
        function () {

            const baseAmount =
                participationData.baseAmount;


            let amount =
                Number(
                    this.value || 0
                );


            /*
             * On ne bloque pas immédiatement
             * l'utilisateur.
             *
             * La validation indiquera
             * si le montant est insuffisant.
             */
            participationData.amount =
                amount;


            const finalAmount =
                document.getElementById(
                    'final_amount'
                );


            if (finalAmount) {

                finalAmount.textContent =
                    formatAmount(amount);

            }


            const waveAmount =
                document.getElementById(
                    'wave_amount'
                );


            if (waveAmount) {

                waveAmount.textContent =
                    formatAmount(amount);

            }


            const paymentAmount =
                document.getElementById(
                    'payment_amount'
                );


            if (paymentAmount) {

                paymentAmount.value =
                    amount;

            }


            clearInvalid(this);

        }
    );

}


/* =========================================================
   NUMÉROS WAVE
========================================================= */

function renderWaveNumbers() {

    const container =
        document.getElementById(
            'wave_numbers'
        );


    if (!container) {
        return;
    }


    container.innerHTML = '';


    const activeNumbers =
        participationConfig.waveNumbers
            .filter(
                wave => wave.active
            );


    activeNumbers.forEach(
        function (wave, index) {

            const button =
                document.createElement(
                    'button'
                );


            button.type =
                'button';


            button.className =
                'wave-number';


            button.dataset.waveId =
                wave.id;


            button.innerHTML = `

                <div class="wave-number-main">

                    <span>
                        ${escapeHtml(wave.label)}
                    </span>

                    <strong>
                        ${escapeHtml(wave.number)}
                    </strong>

                </div>

                <i class="bi bi-circle"></i>

            `;


            button.addEventListener(
                'click',
                function () {

                    selectWave(
                        wave,
                        button
                    );

                }
            );


            container.appendChild(
                button
            );


            /*
             * Premier numéro sélectionné
             */
            if (index === 0) {

                selectWave(
                    wave,
                    button
                );

            }

        }
    );

}


/* =========================================================
   SÉLECTION WAVE
========================================================= */

function selectWave(
    wave,
    button
) {

    document
        .querySelectorAll(
            '.wave-number'
        )
        .forEach(
            item => {

                item.classList.remove(
                    'active'
                );


                const icon =
                    item.querySelector(
                        'i'
                    );


                if (icon) {

                    icon.className =
                        'bi bi-circle';

                }

            }
        );


    button.classList.add(
        'active'
    );


    const icon =
        button.querySelector(
            'i'
        );


    if (icon) {

        icon.className =
            'bi bi-check-circle';

    }


    participationData.waveId =
        wave.id;


    participationData.waveNumber =
        wave.number;


    const error =
        document.getElementById(
            'wave_selection_error'
        );


    if (error) {

        error.classList.remove(
            'show'
        );

    }

}


/* =========================================================
   PREUVE
========================================================= */

function initializeProofInput() {

    const input =
        document.getElementById(
            'payment_proof'
        );


    if (!input) {
        return;
    }


    input.addEventListener(
        'change',
        function () {

            const file =
                this.files[0];


            clearInvalid(this);


            if (!file) {
                return;
            }


            participationData.paymentProof =
                file;


            /*
             * Aperçu immédiat
             */
            const image =
                document.getElementById(
                    'review_proof_image'
                );


            const placeholder =
                document.getElementById(
                    'review_proof_placeholder'
                );


            if (
                image &&
                placeholder
            ) {

                const reader =
                    new FileReader();


                reader.onload =
                    function (event) {

                        image.src =
                            event.target.result;


                        image.classList.add(
                            'visible'
                        );


                        placeholder.classList.add(
                            'hidden'
                        );

                    };


                reader.readAsDataURL(
                    file
                );

            }

        }
    );

}


/* =========================================================
   VALIDATION CHAMPS
========================================================= */

function markInvalid(
    input,
    message
) {

    if (!input) {
        return;
    }


    input.classList.add(
        'is-invalid'
    );


    const field =
        input.closest(
            '.participation-field'
        );


    if (!field) {
        return;
    }


    const error =
        field.querySelector(
            '.field-error'
        );


    if (error) {

        error.textContent =
            message;

    }

}


function clearInvalid(input) {

    if (!input) {
        return;
    }


    input.classList.remove(
        'is-invalid'
    );


    const field =
        input.closest(
            '.participation-field'
        );


    if (!field) {
        return;
    }


    const error =
        field.querySelector(
            '.field-error'
        );


    if (error) {

        error.textContent =
            '';

    }

}


/* =========================================================
   ÉTAPE 1
========================================================= */

function validateStep1() {

    const nameInput =
        document.getElementById(
            'participant_name'
        );


    const phoneInput =
        document.getElementById(
            'participant_phone'
        );


    const quantityInput =
        document.getElementById(
            'participant_quantity'
        );


    const amountInput =
        document.getElementById(
            'participant_amount'
        );


    const name =
        nameInput.value.trim();


    const phone =
        phoneInput.value.trim();


    const quantity =
        Number(
            quantityInput.value
        );


    const amount =
        Number(
            amountInput.value
        );


    let valid =
        true;


    clearInvalid(
        nameInput
    );


    clearInvalid(
        phoneInput
    );


    clearInvalid(
        quantityInput
    );


    clearInvalid(
        amountInput
    );


    if (!name) {

        markInvalid(
            nameInput,
            'Ton nom complet est obligatoire.'
        );

        valid = false;

    }


    if (!phone) {

        markInvalid(
            phoneInput,
            'Ton numéro de téléphone est obligatoire.'
        );

        valid = false;

    }


    if (
        !quantity ||
        quantity < 1
    ) {

        markInvalid(
            quantityInput,
            'Le nombre minimum est de 1 personne.'
        );

        valid = false;

    }


    const baseAmount =
        quantity *
        participationConfig.pricing.basePrice;


    if (
        !amount ||
        amount < baseAmount
    ) {

        markInvalid(
            amountInput,
            `Le montant minimum est de ${formatAmount(baseAmount)} FCFA.`
        );

        valid = false;

    }


    if (!valid) {

        return false;

    }


    participationData.name =
        name;


    participationData.phone =
        phone;


    participationData.quantity =
        quantity;


    participationData.baseAmount =
        baseAmount;


    participationData.amount =
        amount;


    updateParticipationAmount();


    return true;


    /* ========================================
       HISTORIQUE DE PARTICIPATION
    ======================================== */

    const hasPreviousParticipation =
        participationData.hasPreviousParticipation;


    /*
     * Le participant doit obligatoirement
     * répondre Oui ou Non.
     */
    if (hasPreviousParticipation === null) {

        showFieldError(
            document.querySelector('.participation-history'),
            'Veuillez indiquer si vous avez déjà participé au RDSA.'
        );

        return false;
    }


    /*
     * S'il a déjà participé,
     * au moins une édition doit être sélectionnée.
     */
    if (
        hasPreviousParticipation === true &&
        participationData.previousEditions.length === 0
    ) {

        showFieldError(
            document.getElementById(
                'previous-editions-container'
            ),
            'Sélectionnez au moins une édition précédente.'
        );

        return false;
    }

}


/* =========================================================
   ÉTAPE 2
========================================================= */

function validateStep2() {

    if (
        !participationData.waveId
    ) {

        const error =
            document.getElementById(
                'wave_selection_error'
            );


        if (error) {

            error.classList.add(
                'show'
            );

        }


        return false;

    }


    /*
     * Le montant Wave doit correspondre
     * au montant choisi.
     */
    const paymentAmount =
        document.getElementById(
            'payment_amount'
        );


    if (paymentAmount) {

        paymentAmount.value =
            participationData.amount;

    }


    return true;

}


/* =========================================================
   DATE AUTOMATIQUE
========================================================= */

function setAutomaticSubmissionDate() {

    const now =
        new Date();


    participationData.submissionDatetime =
        now.toISOString();


    const display =
        document.getElementById(
            'submission_datetime'
        );


    if (display) {

        display.textContent =
            now.toLocaleString(
                'fr-FR',
                {
                    dateStyle: 'long',
                    timeStyle: 'short'
                }
            );

    }

}


/* =========================================================
   ÉTAPE 3
========================================================= */

function validateStep3() {

    const referenceInput =
        document.getElementById(
            'transaction_reference'
        );


    const amountInput =
        document.getElementById(
            'payment_amount'
        );


    const proofInput =
        document.getElementById(
            'payment_proof'
        );


    const reference =
        referenceInput.value.trim();


    const paymentAmount =
        Number(
            amountInput.value
        );


    const proof =
        proofInput.files[0];


    let valid =
        true;


    clearInvalid(
        referenceInput
    );


    clearInvalid(
        amountInput
    );


    clearInvalid(
        proofInput
    );


    /*
     * Référence
     */
    if (!reference) {

        markInvalid(
            referenceInput,
            'La référence de transaction est obligatoire.'
        );

        valid = false;

    }


    /*
     * Montant
     */
    if (
        !paymentAmount ||
        paymentAmount <
        participationData.amount
    ) {

        markInvalid(
            amountInput,
            `Le montant doit être au minimum de ${formatAmount(participationData.amount)} FCFA.`
        );

        valid = false;

    }


    /*
     * Preuve
     */
    if (!proof) {

        markInvalid(
            proofInput,
            'La capture de paiement est obligatoire.'
        );

        valid = false;

    } else {

        if (
            !participationConfig.payment
                .allowedProofTypes
                .includes(
                    proof.type
                )
        ) {

            markInvalid(
                proofInput,
                'Format accepté : JPG, PNG ou WebP.'
            );

            valid = false;

        }


        if (
            proof.size >
            participationConfig.payment
                .maxProofSize
        ) {

            markInvalid(
                proofInput,
                'La preuve ne doit pas dépasser 5 Mo.'
            );

            valid = false;

        }

    }


    if (!valid) {

        return false;

    }


    participationData.transactionReference =
        reference;


    participationData.paymentAmount =
        paymentAmount;


    participationData.paymentProof =
        proof;


    /*
     * La date est automatique.
     */
    setAutomaticSubmissionDate();


    return true;

}


/* =========================================================
   RÉCAPITULATIF
========================================================= */

function prepareReview() {

    document.getElementById(
        'review_name'
    ).textContent =
        participationData.name;


    document.getElementById(
        'review_phone'
    ).textContent =
        participationData.phone;


    document.getElementById(
        'review_quantity'
    ).textContent =
        `${participationData.quantity} personne${participationData.quantity > 1
            ? 's'
            : ''
        }`;


    document.getElementById(
        'review_amount'
    ).textContent =
        `${formatAmount(participationData.amount)} FCFA`;


    document.getElementById(
        'review_wave'
    ).textContent =
        participationData.waveNumber;


    document.getElementById(
        'review_reference'
    ).textContent =
        participationData.transactionReference;


    document.getElementById(
        'review_payment_amount'
    ).textContent =
        `${formatAmount(participationData.paymentAmount)} FCFA`;


    document.getElementById(
        'review_total'
    ).textContent =
        formatAmount(
            participationData.amount
        );


    const proof =
        participationData.paymentProof;


    document.getElementById(
        'review_proof'
    ).textContent =
        proof
            ? proof.name
            : '—';


    /*
     * Synchronisation ticket
     */
    const ticketName =
        document.getElementById(
            'ticket_name'
        );


    if (ticketName) {

        ticketName.textContent =
            participationData.name;

    }


    const ticketAmount =
        document.getElementById(
            'ticket_amount'
        );


    if (ticketAmount) {

        ticketAmount.textContent =
            `${formatAmount(participationData.amount)} FCFA`;

    }

}


/* =========================================================
   NAVIGATION
========================================================= */

function nextParticipationStep(step) {

    /*
     * Validation avant changement
     */
    if (
        step === 2 &&
        !validateStep1()
    ) {

        return;

    }


    if (
        step === 3 &&
        !validateStep2()
    ) {

        return;

    }


    if (
        step === 4 &&
        !validateStep3()
    ) {

        return;

    }


    /*
     * Si étape 4 :
     * préparer l'aperçu.
     */
    if (step === 4) {

        prepareReview();

    }


    showParticipationStep(
        step
    );

}


/* =========================================================
   AFFICHER UNE ÉTAPE
========================================================= */

function showParticipationStep(
    step
) {

    const steps =
        document.querySelectorAll(
            '.participation-form-step'
        );


    steps.forEach(
        element => {

            element.classList.remove(
                'active'
            );

        }
    );


    const currentStep =
        document.getElementById(
            `participation-step-${step}`
        );


    if (!currentStep) {

        return;

    }


    currentStep.classList.add(
        'active'
    );


    /*
     * Progression
     */
    const progressSteps =
        document.querySelectorAll(
            '.form-step'
        );


    progressSteps.forEach(
        (element, index) => {

            element.classList.toggle(
                'active',
                index < step
            );

        }
    );


    /*
     * Retour en haut de la carte
     */
    const card =
        document.querySelector(
            '.participation-card'
        );


    if (card) {

        card.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
        });

    }

}


/* =========================================================
   CONFIRMATION
========================================================= */

function confirmParticipation() {

    /*
     * POUR LE FRONTEND UNIQUEMENT
     *
     * Plus tard cette fonction fera :
     *
     * POST /participations
     *
     * avec FormData.
     *
     * Le backend Laravel générera alors
     * la vraie référence.
     */


    generateTemporaryTicket();


    showParticipationStep(
        5
    );

}


/* =========================================================
   TICKET TEMPORAIRE
========================================================= */

function generateTemporaryTicket() {

    const randomPart =
        Math.random()
            .toString(36)
            .substring(2, 8)
            .toUpperCase();


    participationData.ticketReference =
        `RDSA3-TK-${randomPart}`;


    const reference =
        document.querySelector(
            '.ticket-reference'
        );


    if (reference) {

        reference.textContent =
            participationData.ticketReference;

    }

}


/* =========================================================
   ESCAPE HTML
========================================================= */

function escapeHtml(
    value
) {

    const div =
        document.createElement(
            'div'
        );


    div.textContent =
        value;


    return div.innerHTML;

}


/* =========================================================
   VALIDATION VISUELLE
========================================================= */

function initializeInputValidation() {

    document
        .querySelectorAll(
            '.rdsa-input'
        )
        .forEach(
            input => {

                input.addEventListener(
                    'input',
                    function () {

                        clearInvalid(
                            this
                        );

                    }
                );


                input.addEventListener(
                    'change',
                    function () {

                        clearInvalid(
                            this
                        );



                    }
                );

            }
        );

}


/* =========================================================
   ACCESSIBILITÉ BLADE
========================================================= */

window.nextParticipationStep =
    nextParticipationStep;


window.confirmParticipation =
    confirmParticipation;











/* ========================================
 DECOMPTE POUR LA DATE
============================================*/

const Days = document.getElementById('days');
const DaysUp = document.getElementById('daysup');
const Hours = document.getElementById('hours');
const Minutes = document.getElementById('minutes');
const Seconds = document.getElementById('seconds');

const targetDate = new Date("february 13 2027 00:00:00").getTime();

function timer() {
    const currentDate = new Date().getTime();
    const distance = targetDate - currentDate;

    const days = Math.floor(distance / 1000 / 60 / 60 / 24);
    const hours = Math.floor(distance / 1000 / 60 / 60) % 24;
    const minutes = Math.floor(distance / 1000 / 60) % 60;
    const seconds = Math.floor(distance / 1000) % 60;

    /* console.log( days + ":" + hours + ":" + minutes + ":" + seconds );*/

    Days.innerHTML = days;
    DaysUp.innerHTML = days;
    Hours.innerHTML = hours;
    Minutes.innerHTML = minutes;
    Seconds.innerHTML = seconds;


}

setInterval(timer, 1000)



/* ========================================
 SLIDES
============================================*/
const gallerySlides = document.querySelectorAll('.gallery-slide');

let currentGallerySlide = 0;

if (gallerySlides.length > 1) {

    setInterval(() => {

        gallerySlides[currentGallerySlide].classList.remove('active');

        currentGallerySlide =
            (currentGallerySlide + 1) % gallerySlides.length;

        gallerySlides[currentGallerySlide].classList.add('active');

    }, 5000);

}

/* ========================================
 MODAL GALLERY
============================================*/

const galleryModal = document.getElementById('galleryModal');
const openGallery = document.getElementById('openGallery');
const closeGallery = document.getElementById('closeGallery');

const galleryImages = document.querySelectorAll('.gallery-image');

const prevPhoto = document.getElementById('prevPhoto');
const nextPhoto = document.getElementById('nextPhoto');

const photoNumber = document.getElementById('photoNumber');

let currentPhoto = 0;


/* OUVRIR */
openGallery.addEventListener('click', function () {

    galleryModal.classList.add('active');

    document.body.style.overflow = 'hidden';

});


/* FERMER */
closeGallery.addEventListener('click', function () {

    galleryModal.classList.remove('active');

    document.body.style.overflow = '';

});


/* PHOTO SUIVANTE */
nextPhoto.addEventListener('click', function () {

    galleryImages[currentPhoto].classList.remove('active');

    currentPhoto++;

    if (currentPhoto >= galleryImages.length) {
        currentPhoto = 0;
    }

    galleryImages[currentPhoto].classList.add('active');

    photoNumber.textContent = currentPhoto + 1;

});


/* PHOTO PRECEDENTE */
prevPhoto.addEventListener('click', function () {

    galleryImages[currentPhoto].classList.remove('active');

    currentPhoto--;

    if (currentPhoto < 0) {
        currentPhoto = galleryImages.length - 1;
    }

    galleryImages[currentPhoto].classList.add('active');

    photoNumber.textContent = currentPhoto + 1;

});


/* FERMER EN CLIQUANT SUR LE FOND */
galleryModal.addEventListener('click', function (event) {

    if (event.target === galleryModal) {

        galleryModal.classList.remove('active');

        document.body.style.overflow = '';

    }

});


/* CLAVIER */
document.addEventListener('keydown', function (event) {

    if (!galleryModal.classList.contains('active')) {
        return;
    }

    if (event.key === 'ArrowRight') {
        nextPhoto.click();
    }

    if (event.key === 'ArrowLeft') {
        prevPhoto.click();
    }

    if (event.key === 'Escape') {
        closeGallery.click();
    }

});
