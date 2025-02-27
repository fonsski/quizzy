let questionCount = 0;

function addQuestion() {
  const container = document.getElementById("questions-container");
  const questionDiv = document.createElement("div");
  questionDiv.className = "question-card";
  questionDiv.dataset.questionId = questionCount;

  questionDiv.innerHTML = `
        <div class="question-header">
            <h3>Вопрос ${questionCount + 1}</h3>
            <button type="button" class="btn btn-danger" onclick="removeQuestion(${questionCount})">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
                Удалить
            </button>
        </div>

        <div class="form-group">
            <label>Текст вопроса</label>
            <input type="text" name="questions[${questionCount}][text]"
                   class="form-control" required placeholder="Введите текст вопроса">
        </div>

        <div class="form-group">
            <label>Тип вопроса</label>
            <select name="questions[${questionCount}][type]" class="form-control"
                    onchange="updateAnswersType(${questionCount}, this.value)">
                <option value="single">Один правильный ответ</option>
                <option value="multiple">Несколько правильных ответов</option>
            </select>
        </div>

        <div class="answers-container">
            <label>Варианты ответов</label>
            <div class="answers-grid"></div>
            <button type="button" class="btn btn-secondary" onclick="addAnswer(${questionCount})" style="margin-top: 12px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Добавить ответ
            </button>
        </div>
    `;

  container.appendChild(questionDiv);
  addAnswer(questionCount); // Add first answer automatically
  questionCount++;
}

function addAnswer(questionId) {
  const container = document.querySelector(
    `[data-question-id="${questionId}"] .answers-grid`,
  );
  const answerCount = container.children.length;

  const answerDiv = document.createElement("div");
  answerDiv.className = "answer-option";

  const questionType = document.querySelector(
    `[name="questions[${questionId}][type]"]`,
  ).value;
  const inputType = questionType === "multiple" ? "checkbox" : "radio";

  answerDiv.innerHTML = `
        <input type="${inputType}" name="questions[${questionId}][answers][${answerCount}][is_correct]" value="1">
        <input type="text" name="questions[${questionId}][answers][${answerCount}][text]"
               class="form-control" placeholder="Введите вариант ответа" required>
        <button type="button" class="btn btn-danger" onclick="this.parentElement.remove()">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    `;

  container.appendChild(answerDiv);
}

function updateAnswersType(questionId, type) {
  const container = document.querySelector(
    `[data-question-id="${questionId}"] .answers-grid`,
  );
  const answers = container.querySelectorAll(".answer-option");

  answers.forEach((answer) => {
    const inputType = type === "multiple" ? "checkbox" : "radio";
    const oldInput = answer.querySelector(
      'input[type="checkbox"], input[type="radio"]',
    );
    const newInput = document.createElement("input");
    newInput.type = inputType;
    newInput.name = oldInput.name;
    newInput.value = oldInput.value;
    newInput.checked = oldInput.checked;
    oldInput.parentNode.replaceChild(newInput, oldInput);
  });
}

function removeQuestion(questionId) {
  if (confirm("Вы уверены, что хотите удалить этот вопрос?")) {
    document.querySelector(`[data-question-id="${questionId}"]`).remove();
  }
}

// Добавляем первый вопрос автоматически
document.addEventListener("DOMContentLoaded", function () {
  addQuestion();
});
