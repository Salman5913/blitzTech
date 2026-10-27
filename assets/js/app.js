/*
Template Name: Qovex - Responsive Bootstrap 4 Admin Dashboard
Author: Themesbrand
Version: 1.0.0
Website: https://themesbrand.com/
Contact: themesbrand@gmail.com
File: Main Js File
*/

$(function () {
    'use strict';

    function initMetisMenu() {
        //metis menu
        $("#side-menu").metisMenu();
    }

    function initLeftMenuCollapse() {
        $('#vertical-menu-btn').on('click', function (event) {
            event.preventDefault();
            $('body').toggleClass('sidebar-enable');
            if ($(window).width() >= 992) {
                $('body').toggleClass('vertical-collpsed');
            } else {
                $('body').removeClass('vertical-collpsed');
            }
        });
    }

    function initActiveMenu() {
        // Activate the menu in the left sidebar based on the URL
        const pageUrl = window.location.href;

        $("#sidebar-menu a").each(function () {
            if (this.href === pageUrl) {
                const $link = $(this).parent("span").length
                    ? $(this).closest("li").find("> a")
                    : $(this);

                $link.addClass("active");

                const $li = $link.parent();
                $li.addClass("mm-active");

                // Traverse up the DOM to apply necessary classes
                $li.parents("ul").addClass("mm-show");
                $li.parents("li").addClass("mm-active");
            }
        });
    }

    function initMenuItem() {
        const pageUrl = window.location.href.split(/[?#]/)[0]; // Get the current page URL

        $(".navbar-nav a").each(function () {
            if (this.href === pageUrl) {
                const $link = $(this).parent("span").length
                    ? $(this).closest("li").find("> a")
                    : $(this);

                $link.addClass("active");
                $link.parents("li").addClass("active"); // Add 'active' to all parent <li> elements
            }
        });
    }

    function initDropdownMenu() {
        $('.dropdown-menu a.dropdown-toggle').on('click', function(e) {
            if (!$(this).next().hasClass('show')) {
                $(this).parents('.dropdown-menu').first().find('.show').removeClass("show");
            }
            var $subMenu = $(this).next(".dropdown-menu");
            $subMenu.toggleClass('show');

            return false;
        });
    }

    function initComponents() {
        $(function () {
            $('[data-toggle="tooltip"]').tooltip()
        })

        $(function () {
            $('[data-toggle="popover"]').popover()
        })
    }

    function init() {
        initMetisMenu();
        initLeftMenuCollapse();
        initActiveMenu();
        initMenuItem();
        initDropdownMenu();
        initComponents();
    }

    init();
});

