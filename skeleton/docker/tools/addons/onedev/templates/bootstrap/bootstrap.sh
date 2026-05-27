#!/bin/sh
set -eu

apk add --no-cache curl git jq >/dev/null

CONFIG_FILE="/workspace/docker/web/settings/addon_configs.json"
DEFAULT_ADMIN_USERNAME="admin"
DEFAULT_ADMIN_PASSWORD="change-me-onedev-admin"
DEFAULT_ADMIN_EMAIL="admin@example.test"
DEFAULT_PROJECT_NAME="{{VIBE4DOCK_PROJECT_NAME}}"

read_config() {
  local key="$1"

  if [ ! -f "$CONFIG_FILE" ]; then
    return 1
  fi

  jq -r --arg key "$key" '.onedev[$key] // empty' "$CONFIG_FILE"
}

first_non_empty() {
  for value in "$@"; do
    if [ -n "${value:-}" ]; then
      printf '%s' "$value"
      return 0
    fi
  done

  return 1
}

slugify() {
  printf '%s' "$1" \
    | tr '[:upper:]' '[:lower:]' \
    | sed -E 's/[^a-z0-9._-]+/-/g; s/^-+//; s/-+$//; s/-{2,}/-/g'
}

ADMIN_USERNAME="$(first_non_empty "$(read_config admin_username || true)" "$DEFAULT_ADMIN_USERNAME")"
ADMIN_PASSWORD="$(first_non_empty "$(read_config admin_password || true)" "$DEFAULT_ADMIN_PASSWORD")"
ADMIN_EMAIL="$(first_non_empty "$(read_config admin_email || true)" "$DEFAULT_ADMIN_EMAIL")"
PROJECT_NAME="$(first_non_empty "$(read_config project_name || true)" "$DEFAULT_PROJECT_NAME")"
PROJECT_PATH="$(slugify "$PROJECT_NAME")"
PROJECT_DESCRIPTION="Managed by Vibe4Dock for the current workspace."
ONEDEV_URL="http://onedev:6610"

if [ -z "$PROJECT_PATH" ]; then
  echo "Unable to derive a valid OneDev project path." >&2
  exit 1
fi

wait_for_onedev() {
  attempts=0
  while [ "$attempts" -lt 120 ]; do
    if curl -fsS -u "$ADMIN_USERNAME:$ADMIN_PASSWORD" "$ONEDEV_URL/~api/users/ids/$ADMIN_USERNAME" >/dev/null 2>&1; then
      return 0
    fi

    attempts=$((attempts + 1))
    sleep 5
  done

  echo "Timed out while waiting for OneDev API to become ready." >&2
  return 1
}

create_project() {
  curl -fsS \
    -u "$ADMIN_USERNAME:$ADMIN_PASSWORD" \
    -H 'Content-Type: application/json' \
    -X POST \
    -d "$(jq -cn \
      --arg name "$PROJECT_NAME" \
      --arg description "$PROJECT_DESCRIPTION" \
      '{name: $name, description: $description, codeManagement: true, packManagement: true, issueManagement: true, timeTracking: true, gitPackConfig: {}, codeAnalysisSetting: {}}')" \
    "$ONEDEV_URL/~api/projects"
}

find_project() {
  curl -fsS \
    -u "$ADMIN_USERNAME:$ADMIN_PASSWORD" \
    --get \
    --data-urlencode "query=\"Name\" is \"$PROJECT_NAME\"" \
    --data "offset=0" \
    --data "count=1" \
    "$ONEDEV_URL/~api/projects"
}

push_repository() {
  local project_path="$1"

  if ! git -C /workspace rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    return 0
  fi

  if ! git -C /workspace rev-parse --verify HEAD >/dev/null 2>&1; then
    return 0
  fi

  auth_header="$(printf '%s:%s' "$ADMIN_USERNAME" "$ADMIN_PASSWORD" | base64 | tr -d '\n')"
  remote_url="$ONEDEV_URL/$project_path.git"

  git -C /workspace -c http.extraHeader="Authorization: Basic $auth_header" push "$remote_url" --all
  git -C /workspace -c http.extraHeader="Authorization: Basic $auth_header" push "$remote_url" --tags
}

wait_for_onedev

existing_project="$(find_project)"
existing_project_id="$(printf '%s' "$existing_project" | jq -r '.[0].id // empty')"

if [ -n "$existing_project_id" ]; then
  exit 0
fi

created_project_id="$(create_project | tr -d '\n\r')"
created_project="$(curl -fsS -u "$ADMIN_USERNAME:$ADMIN_PASSWORD" "$ONEDEV_URL/~api/projects/$created_project_id")"
created_project_path="$(printf '%s' "$created_project" | jq -r '.path // empty')"

if [ -z "$created_project_path" ]; then
  echo "OneDev project was created, but its resolved path could not be read back from the API." >&2
  exit 1
fi

push_repository "$created_project_path"
