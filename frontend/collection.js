const collectionContainer = document.getElementById("my-collection");
const totalCards = document.getElementById("total-cards");
const collectionValue = document.getElementById("collection-value");

// Load saved Pokemon cards
let collection = JSON.parse(
   localStorage.getItem("pokemonCollection") || "[]"
);

// Save collection changes
function saveCollection() {
   localStorage.setItem(
       "pokemonCollection",
       JSON.stringify(collection)
   );
}

// Display saved Pokemon cards
function displayCollection() {
   collectionContainer.innerHTML = "";

   let total = 0;
   collection.forEach(card => {
       total += Number(card.market_price) || 0;
   });

   // Update collection statistics
   totalCards.textContent = collection.length;
   collectionValue.textContent = "$" + total.toFixed(2);

   // Show a message if the collection is empty
   if (collection.length === 0) {
       collectionContainer.textContent =
           "No Pokémon cards added yet.";
       return;
   }

   collection.forEach(card => {
       const cardElement = document.createElement("div");

       const name = document.createElement("h3");
       name.textContent = card.name;

       const image = document.createElement("img");

       image.src = card.image
           ? (card.image.endsWith("/low.webp") ||
               /\.(png|jpe?g|webp|gif)(\?|$)/i.test(card.image)
               ? card.image
               : card.image + "/low.webp")
           : "https://placehold.co/180x250?text=No+Image";

       image.alt = card.name;

       image.onerror = function () {
           this.onerror = null;
           this.src = "https://placehold.co/180x250?text=No+Image";
       };

       const details = document.createElement("p");
       details.textContent =
           card.set_name + " #" + card.number + " - $" +
           (Number(card.market_price) || 0).toFixed(2);

       const removeButton = document.createElement("button");
       removeButton.textContent = "Remove";

       removeButton.addEventListener("click", () => {
           collection = collection.filter(
               item => item.id !== card.id
           );

           saveCollection();
           displayCollection();
       });

       cardElement.append(name, image, details, removeButton);
       collectionContainer.appendChild(cardElement);
   });
}

// Display collection when page loads
displayCollection();
