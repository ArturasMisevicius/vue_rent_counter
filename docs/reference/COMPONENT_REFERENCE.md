# Complete daisyUI Component Reference

## Quick Component Lookup

This is a quick reference for all 60+ daisyUI components with basic usage examples.

## Actions (11 Components)

### Button
```blade
<button class="btn btn-primary">Primary Button</button>
<button class="btn btn-secondary">Secondary</button>
<button class="btn btn-outline">Outline</button>
<button class="btn btn-ghost">Ghost</button>
<button class="btn btn-link">Link</button>
```

### Dropdown
```blade
<div class="dropdown">
    <label tabindex="0" class="btn">Click</label>
    <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-52">
        <li><a>Item 1</a></li>
        <li><a>Item 2</a></li>
    </ul>
</div>
```

### Modal
```blade
<dialog id="my_modal" class="modal">
    <div class="modal-box">
        <h3 class="font-bold text-lg">Modal Title</h3>
        <p class="py-4">Modal content</p>
        <div class="modal-action">
            <form method="dialog">
                <button class="btn">Close</button>
            </form>
        </div>
    </div>
</dialog>
```

### Swap
```blade
<label class="swap">
    <input type="checkbox" />
    <div class="swap-on">ON</div>
    <div class="swap-off">OFF</div>
</label>
```

### Theme Controller
```blade
<input type="checkbox" value="dark" class="toggle theme-controller"/>
```

### Drawer
```blade
<div class="drawer">
    <input id="my-drawer" type="checkbox" class="drawer-toggle" />
    <div class="drawer-content">
        <label for="my-drawer" class="btn btn-primary drawer-button">Open drawer</label>
    </div>
    <div class="drawer-side">
        <label for="my-drawer" class="drawer-overlay"></label>
        <ul class="menu p-4 w-80 bg-base-100">
            <li><a>Item 1</a></li>
            <li><a>Item 2</a></li>
        </ul>
    </div>
</div>
```

### Menu
```blade
<ul class="menu bg-base-100 w-56 rounded-box">
    <li><a>Item 1</a></li>
    <li><a>Item 2</a></li>
    <li><a>Item 3</a></li>
</ul>
```

### Tooltip
```blade
<div class="tooltip" data-tip="Tooltip text">
    <button class="btn">Hover me</button>
</div>
```

### Toast
```blade
<div class="toast toast-top toast-end">
    <div class="alert alert-info">
        <span>New message arrived.</span>
    </div>
</div>
```

### File Input
```blade
<input type="file" class="file-input file-input-bordered w-full" />
```

### Rating
```blade
<div class="rating">
    <input type="radio" name="rating-1" class="mask mask-star" />
    <input type="radio" name="rating-1" class="mask mask-star" checked />
    <input type="radio" name="rating-1" class="mask mask-star" />
</div>
```

## Data Display (13 Components)

### Accordion
```blade
<div class="collapse collapse-arrow bg-base-100">
    <input type="checkbox" />
    <div class="collapse-title text-xl font-medium">
        Click to open
    </div>
    <div class="collapse-content">
        <p>Content here</p>
    </div>
</div>
```

### Avatar
```blade
<div class="avatar">
    <div class="w-24 rounded-full">
        <img src="/images/avatar.jpg" />
    </div>
</div>
```

### Badge
```blade
<div class="badge">Default</div>
<div class="badge badge-primary">Primary</div>
<div class="badge badge-secondary">Secondary</div>
<div class="badge badge-accent">Accent</div>
<div class="badge badge-ghost">Ghost</div>
```

### Card
```blade
<div class="card bg-base-100 shadow-xl">
    <figure><img src="/images/photo.jpg" alt="Photo" /></figure>
    <div class="card-body">
        <h2 class="card-title">Card Title</h2>
        <p>Card content</p>
        <div class="card-actions justify-end">
            <button class="btn btn-primary">Action</button>
        </div>
    </div>
</div>
```

### Carousel
```blade
<div class="carousel w-full">
    <div id="slide1" class="carousel-item relative w-full">
        <img src="/images/1.jpg" class="w-full" />
    </div>
    <div id="slide2" class="carousel-item relative w-full">
        <img src="/images/2.jpg" class="w-full" />
    </div>
</div>
```

### Chat Bubble
```blade
<div class="chat chat-start">
    <div class="chat-bubble">Hello!</div>
</div>
<div class="chat chat-end">
    <div class="chat-bubble">Hi there!</div>
</div>
```

### Collapse
```blade
<div class="collapse bg-base-100">
    <input type="checkbox" />
    <div class="collapse-title">Click to expand</div>
    <div class="collapse-content">
        <p>Hidden content</p>
    </div>
</div>
```

### Countdown
```blade
<span class="countdown font-mono text-6xl">
    <span style="--value:15;"></span>
</span>
```

### Diff
```blade
<div class="diff aspect-[16/9]">
    <div class="diff-item-1">
        <img alt="Before" src="/images/before.jpg" />
    </div>
    <div class="diff-item-2">
        <img alt="After" src="/images/after.jpg" />
    </div>
    <div class="diff-resizer"></div>
</div>
```

### Kbd
```blade
<kbd class="kbd">Ctrl</kbd> + <kbd class="kbd">C</kbd>
```

### Stat
```blade
<div class="stats shadow">
    <div class="stat">
        <div class="stat-title">Total Page Views</div>
        <div class="stat-value">89,400</div>
        <div class="stat-desc">21% more than last month</div>
    </div>
</div>
```

### Table
```blade
<table class="table">
    <thead>
        <tr>
            <th>Name</th>
            <th>Job</th>
            <th>Company</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>John Doe</td>
            <td>Developer</td>
            <td>Acme Corp</td>
        </tr>
    </tbody>
</table>
```

### Timeline
```blade
<ul class="timeline">
    <li>
        <div class="timeline-start">1984</div>
        <div class="timeline-middle">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
        </div>
        <div class="timeline-end timeline-box">First Event</div>
        <hr/>
    </li>
</ul>
```

## Data Input (13 Components)

### Checkbox
```blade
<input type="checkbox" class="checkbox" />
<input type="checkbox" class="checkbox checkbox-primary" checked />
```

### File Input
```blade
<input type="file" class="file-input file-input-bordered w-full" />
```

### Radio
```blade
<input type="radio" name="radio-1" class="radio" checked />
<input type="radio" name="radio-1" class="radio" />
```

### Range
```blade
<input type="range" min="0" max="100" value="40" class="range" />
```

### Rating
```blade
<div class="rating">
    <input type="radio" name="rating-2" class="mask mask-star-2 bg-orange-400" />
    <input type="radio" name="rating-2" class="mask mask-star-2 bg-orange-400" checked />
</div>
```

### Select
```blade
<select class="select select-bordered w-full">
    <option disabled selected>Pick one</option>
    <option>Option 1</option>
    <option>Option 2</option>
</select>
```

### Text Input
```blade
<input type="text" placeholder="Type here" class="input input-bordered w-full" />
```

### Textarea
```blade
<textarea class="textarea textarea-bordered" placeholder="Bio"></textarea>
```

### Toggle
```blade
<input type="checkbox" class="toggle" checked />
<input type="checkbox" class="toggle toggle-primary" checked />
```

### Form Control
```blade
<div class="form-control w-full">
    <label class="label">
        <span class="label-text">Label</span>
    </label>
    <input type="text" class="input input-bordered w-full" />
    <label class="label">
        <span class="label-text-alt">Helper text</span>
    </label>
</div>
```

### Label
```blade
<label class="label">
    <span class="label-text">Label text</span>
    <span class="label-text-alt">Alt label</span>
</label>
```

### Input Group
```blade
<div class="join">
    <input class="input input-bordered join-item" placeholder="Email"/>
    <button class="btn join-item">Subscribe</button>
</div>
```

### Join
```blade
<div class="join">
    <button class="btn join-item">Button 1</button>
    <button class="btn join-item">Button 2</button>
    <button class="btn join-item">Button 3</button>
</div>
```

## Feedback (6 Components)

### Alert
```blade
<div class="alert alert-info">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    </svg>
    <span>Info message</span>
</div>
```

### Loading
```blade
<span class="loading loading-spinner loading-lg"></span>
<span class="loading loading-dots loading-lg"></span>
<span class="loading loading-ring loading-lg"></span>
```

### Progress
```blade
<progress class="progress w-56" value="70" max="100"></progress>
<progress class="progress progress-primary w-56" value="70" max="100"></progress>
```

### Radial Progress
```blade
<div class="radial-progress" style="--value:70;">70%</div>
```

### Skeleton
```blade
<div class="skeleton h-32 w-full"></div>
<div class="skeleton h-4 w-28"></div>
<div class="skeleton h-4 w-full"></div>
```

### Toast
```blade
<div class="toast">
    <div class="alert alert-info">
        <span>New message arrived.</span>
    </div>
</div>
```

## Layout (8 Components)

### Artboard
```blade
<div class="artboard artboard-demo phone-1">320×568</div>
```

### Divider
```blade
<div class="divider">OR</div>
```

### Drawer
```blade
<div class="drawer">
    <input id="my-drawer" type="checkbox" class="drawer-toggle" />
    <div class="drawer-content">
        <label for="my-drawer" class="btn">Open drawer</label>
    </div>
    <div class="drawer-side">
        <label for="my-drawer" class="drawer-overlay"></label>
        <ul class="menu p-4 w-80 bg-base-100">
            <li><a>Sidebar Item 1</a></li>
        </ul>
    </div>
</div>
```

### Footer
```blade
<footer class="footer p-10 bg-base-200 text-base-content">
    <div>
        <span class="footer-title">Services</span>
        <a class="link link-hover">Branding</a>
        <a class="link link-hover">Design</a>
    </div>
</footer>
```

### Hero
```blade
<div class="hero min-h-screen bg-base-200">
    <div class="hero-content text-center">
        <div class="max-w-md">
            <h1 class="text-5xl font-bold">Hello there</h1>
            <p class="py-6">Welcome to our platform</p>
            <button class="btn btn-primary">Get Started</button>
        </div>
    </div>
</div>
```

### Indicator
```blade
<div class="indicator">
    <span class="indicator-item badge badge-secondary">new</span>
    <button class="btn">Inbox</button>
</div>
```

### Join
```blade
<div class="join">
    <button class="btn join-item">Button</button>
    <button class="btn join-item">Button</button>
</div>
```

### Stack
```blade
<div class="stack">
    <div class="card shadow-md bg-primary text-primary-content">
        <div class="card-body">A</div>
    </div>
    <div class="card shadow bg-primary text-primary-content">
        <div class="card-body">B</div>
    </div>
</div>
```

## Navigation (9 Components)

### Breadcrumbs
```blade
<div class="text-sm breadcrumbs">
    <ul>
        <li><a>Home</a></li>
        <li><a>Documents</a></li>
        <li>Add Document</li>
    </ul>
</div>
```

### Bottom Navigation
```blade
<div class="btm-nav">
    <button>
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
        </svg>
    </button>
</div>
```

### Link
```blade
<a class="link">I'm a simple link</a>
<a class="link link-primary">Primary link</a>
<a class="link link-hover">Hover link</a>
```

### Menu
```blade
<ul class="menu bg-base-100 w-56 rounded-box">
    <li><a>Item 1</a></li>
    <li><a>Item 2</a></li>
    <li><a>Item 3</a></li>
</ul>
```

### Navbar
```blade
<div class="navbar bg-base-100">
    <div class="flex-1">
        <a class="btn btn-ghost text-xl">daisyUI</a>
    </div>
    <div class="flex-none">
        <button class="btn btn-square btn-ghost">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>
</div>
```

### Pagination
```blade
<div class="join">
    <button class="join-item btn">«</button>
    <button class="join-item btn">Page 22</button>
    <button class="join-item btn">»</button>
</div>
```

### Steps
```blade
<ul class="steps">
    <li class="step step-primary">Register</li>
    <li class="step step-primary">Choose plan</li>
    <li class="step">Purchase</li>
    <li class="step">Receive Product</li>
</ul>
```

### Tab
```blade
<div class="tabs">
    <a class="tab">Tab 1</a>
    <a class="tab tab-active">Tab 2</a>
    <a class="tab">Tab 3</a>
</div>
```

### Sidebar
```blade
<ul class="menu bg-base-100 w-56">
    <li><a>Item 1</a></li>
    <li><a>Item 2</a></li>
    <li><a>Item 3</a></li>
</ul>
```

## Color Variants

Most components support these color variants:
- `primary` - Primary brand color
- `secondary` - Secondary brand color
- `accent` - Accent color
- `neutral` - Neutral color
- `info` - Information color
- `success` - Success color
- `warning` - Warning color
- `error` - Error color
- `ghost` - Transparent background

## Size Variants

Many components support these size variants:
- `xs` - Extra small
- `sm` - Small
- `md` - Medium (default)
- `lg` - Large

## State Variants

Common state classes:
- `disabled` - Disabled state
- `active` - Active state
- `loading` - Loading state
- `hover` - Hover state (with `:hover`)
- `focus` - Focus state (with `:focus`)

## Resources

- [Full daisyUI Documentation](https://daisyui.com/)
- [Component Examples](https://daisyui.com/components/)
- [Theme Generator](https://daisyui.com/theme-generator/)
- Internal: `/design/components/` for detailed docs
