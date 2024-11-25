#!/usr/bin/env bash

# Enable strict mode
set -euo pipefail
IFS=$'\n\t'

# Function to display usage and exit
usage() {
    echo "Usage: $0 <version> <type> <changelog_dir> <changelog_file>"
    exit 1
}

# Ensure the correct number of arguments are provided
if [[ "$#" -ne 4 ]]; then
    echo "Error: Invalid number of arguments."
    usage
fi

# Assign arguments to variables
VERSION="$1"
TYPE="$2"
CHANGELOG_DIR="$3"
CHANGELOG_FILE="$4"

# Validate input
if [[ -z "$VERSION" || -z "$TYPE" || -z "$CHANGELOG_DIR" || -z "$CHANGELOG_FILE" ]]; then
    echo "Error: One or more arguments are empty."
    usage
fi

# Define the output file
OUTPUT_DIR="${CHANGELOG_DIR}"
OUTPUT_FILE="${OUTPUT_DIR}/${VERSION}.md"

# Create the releases directory if it doesn't exist
mkdir -p "$OUTPUT_DIR"

# Check if the changelog file exists
if [[ ! -f "$CHANGELOG_FILE" ]]; then
    echo "Error: Changelog file '${CHANGELOG_FILE}' does not exist."
    exit 1
fi

# Write the Markdown content to the output file
{
    echo "# Release ${VERSION}"
    echo
    echo "| Information          | Value                 |"
    echo "|----------------------|-----------------------|"
    echo "| Version              | ${VERSION}            |"
    echo "| Release Type         | ${TYPE}               |"
    echo "| Maintenance Channel  | post via pipeline     |"
    echo "| Issue                | create via pipeline   |"
} > "$OUTPUT_FILE"

# Append the changelog content to the output file
{
    echo
    echo "## Changelog"
    cat "$CHANGELOG_FILE"
} >> "$OUTPUT_FILE"

# Confirm success
echo "Release file created: ${OUTPUT_FILE}"
