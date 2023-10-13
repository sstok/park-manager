import { startStimulusApp } from '@symfony/stimulus-bridge';
import { definitionsFromContext } from "stimulus/webpack-helpers";

// Registers Stimulus controllers from controllers.json and in the controllers/ directory
export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.(j|t)sx?$/
));

// Import and register all TailwindCSS Components
import { Dropdown, Modal, Tabs, Popover, Toggle, Slideover } from "tailwindcss-stimulus-components";
app.register('dropdown', Dropdown);
// app.register('modal', Modal);
// app.register('tabs', Tabs);
// app.register('popover', Popover);
app.register('toggle', Toggle);
// app.register('slideover', Slideover);
