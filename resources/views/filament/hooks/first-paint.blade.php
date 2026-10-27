{{-- Later in the head than the Vite theme, so equal-specificity rules win once that file arrives. Menu panels open via an inline display, so this rule must not use !important. --}}
<style>
    .fi-dropdown-panel {
        display: none;
    }

    .filament-apex-charts-chart,
    .filament-apex-charts-chart-object {
        min-height: 18rem;
        width: 100%;
    }

    @media (min-width: 1024px) {
        .fi-body:not(.fi-body-has-sidebar-fully-collapsible-on-desktop) .fi-sidebar:not(.fi-sidebar-open) {
            transform: translateX(0);
        }
    }

    .fi-body.fi-body-has-navigation:not(.fi-body-has-sidebar-collapsible-on-desktop):not(.fi-body-has-sidebar-fully-collapsible-on-desktop):not(.fi-body-has-top-navigation) .fi-main-ctn {
        opacity: 1;
    }
</style>
<script>
    document.addEventListener('livewire:init', () => {
        const fitCharts = () => {
            requestAnimationFrame(() => window.dispatchEvent(new Event('resize')))
        }

        fitCharts()

        Livewire.hook('commit', ({ succeed }) => {
            succeed(() => fitCharts())
        })
    })
</script>
