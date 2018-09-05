#!/bin/bash

BASH_COLOR_BLACK="\033[0;30m"
BASH_COLOR_RED="\033[0;31m"
BASH_COLOR_GREEN="\033[0;32m"
BASH_COLOR_ORANGE="\033[0;33m"
BASH_COLOR_BLUE="\033[0;34m"
BASH_COLOR_PURPLE="\033[0;35m"
BASH_COLOR_CYAN="\033[0;36m"
BASH_COLOR_YELLOW="\033[1;33m"
BASH_COLOR_LIGHT_GRAY="\033[0;37m"
BASH_COLOR_DARK_GRAY="\033[1;30m"
BASH_COLOR_LIGHT_RED="\033[1;31m"
BASH_COLOR_LIGHT_GREEN="\033[1;32m"
BASH_COLOR_LIGHT_BLUE="\033[1;34m"
BASH_COLOR_LIGHT_PURPLE="\033[1;35m"
BASH_COLOR_LIGHT_CYAN="\033[1;36m"
BASH_COLOR_WHITE="\033[1;37m"

BASH_COLOR_RESET="\033[1;39m"

function echo-color() {
    echo -e "${*}${BASH_COLOR_RESET}"
}

function echo-red() {
    echo-color "${BASH_COLOR_RED}" "$*"
}
function echo-green() {
    echo-color "${BASH_COLOR_GREEN}" "$*"
}
function echo-cyan() {
    echo-color "${BASH_COLOR_CYAN}" "$*"
}
function echo-yellow() {
    echo-color "${BASH_COLOR_YELLOW}" "$*"
}
function echo-blue() {
    echo-color "${BASH_COLOR_YELLOW}" "$*"
}

function check-last-code() {
    RETURN_CODE="$?"

    if [ "${RETURN_CODE}" != "0" ] ; then
        echo-red "[ ERROR ] - ${1}"

        if [ "${3}" != "" ] ; then ${3} ; fi

        if [ "${2}" != "" ] ; then
            exit ${2}
        else
            exit 255
        fi
    fi
}
