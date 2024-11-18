import { createApp } from "vue";
import App1 from "./components/app.vue";
import App2 from "./components/HelloWorld.vue";

const App = {
    components: {
        App1,
        App2,
    },
    template: `
        <div>
            <app-1></app-1>
            <app-2></app-2>
        </div>
    `,
};

createApp(App).mount("#app");
