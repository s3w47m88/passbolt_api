#!/usr/bin/env bash
set -euo pipefail

# Lightweight hadolint bootstrapper for pre-commit
#
# Uses system hadolint if available. Otherwise downloads a platform-appropriate
# static binary into .bin/hadolint and executes it.

if command -v hadolint >/dev/null 2>&1; then
	exec hadolint --ignore DL3008 "$@"
fi

BIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)/.bin"
mkdir -p "$BIN_DIR"

TARGET="$BIN_DIR/hadolint"
VERSION="v2.12.0"

run_docker() {
	if ! command -v docker >/dev/null 2>&1; then
		echo "hadolint failed and docker is not available for fallback." >&2
		return 1
	fi

	# Run hadolint in a container to avoid host-arch binary issues (common on macOS).
	docker run --rm \
		-v "$PWD:/work" \
		-w /work \
		"hadolint/hadolint:${VERSION}" \
		hadolint --ignore DL3008 "$@"
}

if [ ! -x "$TARGET" ]; then
	OS="$(uname -s)"
	ARCH="$(uname -m)"

	case "$OS" in
	Linux) DL_OS="Linux" ;;
	Darwin) DL_OS="Darwin" ;;
	*)
		echo "Unsupported OS: $OS" >&2
		exit 1
		;;
	esac

	case "$ARCH" in
	x86_64 | amd64) DL_ARCH="x86_64" ;;
	arm64 | aarch64) DL_ARCH="arm64" ;;
	*)
		echo "Unsupported ARCH: $ARCH" >&2
		exit 1
		;;
	esac

	download() {
		local arch="$1"
		local url="https://github.com/hadolint/hadolint/releases/download/${VERSION}/hadolint-${DL_OS}-${arch}"
		echo "Downloading hadolint ${VERSION} for ${DL_OS}/${arch}..." >&2
		curl -fLsS "$url" -o "$TARGET"
	}

	if ! download "$DL_ARCH"; then
		if [ "$DL_OS" = "Darwin" ] && [ "$DL_ARCH" = "arm64" ]; then
			echo "Arm64 binary not found; falling back to x86_64 (Rosetta required)..." >&2
			download "x86_64" || {
				echo "Failed to download hadolint for both arm64 and x86_64." >&2
				exit 1
			}
		else
			echo "Failed to download hadolint for ${DL_OS}/${DL_ARCH}." >&2
			exit 1
		fi
	fi

	chmod +x "$TARGET"
fi

"$TARGET" --ignore DL3008 "$@" || {
	code=$?
	# 139 = SIGSEGV on many shells; treat any non-zero as eligible for fallback on macOS.
	if [ "$(uname -s)" = "Darwin" ]; then
		echo "hadolint binary failed with exit code $code; falling back to docker image." >&2
		run_docker "$@"
		exit $?
	fi
	exit "$code"
}
