const collectionContainer = document.getElementById("my-collection");
const totalCards = document.getElementById("total-cards");
const collectionValue = document.getElementById("collection-value");

// Load saved Pokemon cards
let collection = [];

async function collectionRequest(action, cardId) {
   const response = await fetch("collection.php", {
       method: "POST",
       headers: { "Content-Type": "application/json" },
       credentials: "same-origin",
       body: JSON.stringify({ action, cardId })
   });

   return response.json();
}

async function loadCollection() {
   const data = await collectionRequest("get_collection");

   if (data.success) {
       collection = data.collection;
   }

   displayCollection();
}

// Display saved Pokemon cards
function displayCollection() {
   collectionContainer.innerHTML = "";

   let total = 0;
   let count = 0;
   collection.forEach(card => {
       total += (Number(card.market_price) || 0) * Number(card.quantity);
       count += Number(card.quantity);
   });

   // Update collection statistics
   totalCards.textContent = count;
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
       name.textContent = card.name + " x" + card.quantity;

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

       const detailsButton = document.createElement("button");
       detailsButton.textContent = "Details";

       detailsButton.addEventListener("click", () => {
           sessionStorage.setItem(
               "selectedPokemonCard",
               JSON.stringify({ ...card, image_url: image.src })
           );


           window.location.href = "cardDetails.html";
       });


       const removeButton = document.createElement("button");
       removeButton.textContent = "Remove";


       removeButton.addEventListener("click", async () => {
           await collectionRequest("remove_from_collection", card.id);
           loadCollection();
       });


       cardElement.append(name, image, detailsButton, removeButton);
       collectionContainer.appendChild(cardElement);
   });
}


// Display collection when page loads
loadCollection();
