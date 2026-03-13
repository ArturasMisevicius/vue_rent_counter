# Alpine.js Quick Reference Guide

## Overview

Quick reference for Alpine.js patterns used in the Vilnius Utilities Billing application.

## Basic Patterns

### Component Initialization

```blade
<div x-data="componentName()">
    <!-- Component content -->
</div>

@push('scripts')
<script>
function componentName() {
    return {
        // Data
     