/*search.html*/
// В макете показаны 3 из 24 рейсов.
// Класс, пересадки и длительность — демонстрационные метаданные.
// Длительность указана отдельно с учётом разных часовых поясов.
const flights = [
  {
    id: "su", code: "SU", airline: "Аэрофлот",
    departure: "08:20", arrival: "09:35", airport: "SVO",
    price: 5400, stops: 0, cabin: "economy", duration: 195
  },
  {
    id: "s7", code: "S7", airline: "S7 Airlines",
    departure: "11:45", arrival: "13:00", airport: "DME",
    price: 4850, stops: 0, cabin: "economy", duration: 195
  },
  {
    id: "ut", code: "UT", airline: "Utair",
    departure: "15:10", arrival: "16:25", airport: "VKO",
    price: 4200, stops: 0, cabin: "economy", duration: 195
  }
];

const cart = new Set();
const list = document.querySelector("#flight-list");
const count = document.querySelector("#result-count");
const cartButton = document.querySelector("#cart-button");
const cartDialog = document.querySelector("#cart-dialog");
const cartContent = document.querySelector("#cart-content");

const money = value =>
  new Intl.NumberFormat("ru-RU").format(value) + " ₽";

function renderFlights(items) {
  list.innerHTML = items.length
    ? items.map(flight => `
      <article class="flight" aria-label="${flight.airline}, вылет ${flight.departure}">
        <div class="airline">
          <span class="airline-code">${flight.code}</span>
          <span>${flight.airline}</span>
        </div>

        <div class="route">
          <div class="airport">
            <time class="time">${flight.departure}</time>
            <span class="city">Уфа (UFA)</span>
          </div>
          <div class="route-line" aria-hidden="true"><span>✈</span></div>
          <div class="airport">
            <time class="time">${flight.arrival}</time>
            <span class="city">Москва (${flight.airport})</span>
          </div>
        </div>

        <div class="fare">
          <span class="price">${money(flight.price)}</span>
          <button class="button button-orange"
                  data-flight="${flight.id}"
                  aria-pressed="${cart.has(flight.id)}">
            ${cart.has(flight.id) ? "В корзине" : "Выбрать"}
          </button>
        </div>
      </article>
    `).join("")
    : '<p class="empty">Рейсов по выбранным фильтрам нет.</p>';
}

let visibleFlights = flights;

document.querySelector("#filters").addEventListener("submit", event => {
  event.preventDefault();

  const data = new FormData(event.currentTarget);
  const prices = data.getAll("price");
  const cabins = data.getAll("class");
  const stops = data.get("stops");
  const sort = data.get("sort");

  visibleFlights = flights.filter(flight => {
    const priceMatches = prices.length === 0 || prices.some(range => {
      if (range === "low") return flight.price < 5000;
      if (range === "medium") {
        return flight.price >= 5000 && flight.price < 10000;
      }
      return flight.price >= 10000;
    });

    const stopsMatch = stops === "any" || flight.stops === Number(stops);
    const cabinMatches = cabins.length === 0 || cabins.includes(flight.cabin);

    return priceMatches && stopsMatch && cabinMatches;
  });

  visibleFlights.sort((a, b) => {
    if (sort === "departure") {
      return a.departure.localeCompare(b.departure);
    }
    if (sort === "duration") return a.duration - b.duration;
    return a.price - b.price;
  });

  count.textContent =
    `Показано ${visibleFlights.length} из ${flights.length} демонстрационных рейсов на 15 июня 2024`;

  renderFlights(visibleFlights);
});

list.addEventListener("click", event => {
  const button = event.target.closest("[data-flight]");
  if (!button) return;

  cart.add(button.dataset.flight);
  cartButton.textContent = `Корзина (${cart.size})`;
  renderFlights(visibleFlights);
});

function renderCart() {
  const selected = flights.filter(flight => cart.has(flight.id));

  cartContent.innerHTML = selected.length
    ? selected.map(flight => `
        <div class="cart-item">
          <p><strong>${flight.airline}</strong> · ${flight.departure}</p>
          <p>Уфа → Москва (${flight.airport}) · ${money(flight.price)}</p>
          <button class="remove" data-remove="${flight.id}">Удалить</button>
        </div>
      `).join("") +
      `<p><strong>Итого: ${money(
        selected.reduce((total, flight) => total + flight.price, 0)
      )}</strong></p>
      <p><a class="button button-orange" href="cart.html">Перейти в корзину</a></p>`
    : "<p>Корзина пока пуста.</p>";
}

cartButton.addEventListener("click", () => {
  renderCart();
  cartDialog.showModal();
});

cartContent.addEventListener("click", event => {
  const button = event.target.closest("[data-remove]");
  if (!button) return;

  cart.delete(button.dataset.remove);
  cartButton.textContent = cart.size ? `Корзина (${cart.size})` : "Корзина";
  renderCart();
  renderFlights(visibleFlights);
});

document.querySelector("#login-button").addEventListener("click", () => {
  document.querySelector("#login-dialog").showModal();
});

document.querySelectorAll("[data-close]").forEach(button => {
  button.addEventListener("click", () => button.closest("dialog").close());
});

// Начальное отображение сохраняет порядок карточек из макета.
// Фильтры и сортировка применяются после нажатия «Применить».
renderFlights(flights);
