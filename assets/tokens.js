/**
 * Draws every custom property that the site's stylesheets define on `:root` or on an attribute
 * selector like `[data-tone="dark"]` that redefines them, read through the CSSOM and sorted by what its value resolves to:
 * colours, the contexts that redefine them, type, lengths, shadows, easings, durations and the rest,
 * then the ones that components define on their own selectors, like `.stack { --stack-gap }`
 */
const container = document.querySelector(".playground-ui-tokens");
const context = document.createElement("canvas").getContext("2d", { willReadFrequently: true });

const rules = [];
const pending = [...document.styleSheets].flatMap((sheet) => {
  try {
    return [...sheet.cssRules];
  } catch {
    return [];
  }
});

while (pending.length) {
  const rule = pending.shift();
  if (rule.style) rules.push(rule);
  if (rule.cssRules) pending.unshift(...rule.cssRules);
}

const splitSelectors = (selector) => {
  const parts = [];
  let depth = 0;
  let start = 0;

  for (let index = 0; index < selector.length; index++) {
    if ("([".includes(selector[index])) depth++;
    if (")]".includes(selector[index])) depth--;
    if (selector[index] === "," && depth === 0) {
      parts.push(selector.slice(start, index).trim());
      start = index + 1;
    }
  }

  return [...parts, selector.slice(start).trim()];
};

const selectorOf = (rule) => {
  let parent = rule.parentRule;
  while (parent && parent.selectorText === undefined) parent = parent.parentRule;
  if (!parent) return rule.selectorText ?? "";

  const outer = selectorOf(parent);
  const wrapped = splitSelectors(outer).length > 1 ? `:is(${outer})` : outer;
  if (rule.selectorText === undefined) return outer;

  return rule.selectorText.includes("&") ? rule.selectorText.replaceAll("&", wrapped) : `${wrapped} ${rule.selectorText}`;
};

const declared = new Map();
const contexts = new Map();
const scoped = new Map();
const sharedWithRoot = new Set();
const usage = new Map();

for (const rule of rules) {
  const properties = [...rule.style];

  for (const property of properties.filter((property) => !property.startsWith("--"))) {
    for (const [, name] of rule.style.getPropertyValue(property).matchAll(/var\((--[\w-]+)/g)) {
      if (!usage.has(property)) usage.set(property, new Set());
      usage.get(property).add(name);
    }
  }

  const tokens = properties.filter((property) => property.startsWith("--"));
  if (!tokens.length) continue;
  const selectors = splitSelectors(selectorOf(rule));

  for (const selector of selectors) {
    const match = selector.match(/^\[([\w-]+)="([^"]+)"\]$/);

    if (selector !== ":root" && !selector.includes("playground-ui")) {
      if (!scoped.has(selector)) scoped.set(selector, new Map());
      for (const token of tokens) scoped.get(selector).set(token, rule.style.getPropertyValue(token).trim());
    }

    if (selector !== ":root" && !match) continue;

    if (match && !contexts.has(selector)) {
      contexts.set(selector, { selector, attributes: { [match[1]]: match[2] }, tokens: new Map() });
    }

    if (match && selectors.includes(":root")) sharedWithRoot.add(selector);

    const target = match ? contexts.get(selector).tokens : declared;
    for (const token of tokens) target.set(token, rule.style.getPropertyValue(token).trim());
  }
}

for (const [selector, context] of contexts) {
  if (![...context.tokens.keys()].some((name) => declared.has(name))) contexts.delete(selector);
}

const isContextual = (name) => [...contexts.values()].some((context) => context.tokens.has(name));
const rootRoles = new Map(
  [...declared].filter(([name]) => isContextual(name) && ![...sharedWithRoot].some((selector) => contexts.get(selector).tokens.has(name))),
);

if (rootRoles.size) {
  contexts.set(":root", { selector: ":root", attributes: {}, tokens: rootRoles });
}

const bodyRule = rules.find((rule) => rule.selectorText === "body");
const referenceOf = (value) => value?.match(/^var\((--[\w-]+)\)$/)?.[1];
const surfaceToken = referenceOf(bodyRule?.style.getPropertyValue("background-color"));
const textToken = referenceOf(bodyRule?.style.getPropertyValue("color"));

const escape = (text) => text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
const valueOf = (element, name) => getComputedStyle(element).getPropertyValue(name).trim();

const channels = (color) => {
  if (/^(rgba?\(|color\(srgb )/.test(color)) {
    const [r, g, b, a = 1] = color.match(/[\d.]+/g).map(Number);
    const scale = color.startsWith("color(") ? 1 : 255;

    return { r: r / scale, g: g / scale, b: b / scale, a };
  }

  context.clearRect(0, 0, 1, 1);
  context.fillStyle = color;
  context.fillRect(0, 0, 1, 1);
  const [r, g, b, a] = context.getImageData(0, 0, 1, 1).data;

  return { r: r / 255, g: g / 255, b: b / 255, a: a / 255 };
};

const colourIn = (element, name) => {
  const probe = document.createElement("span");
  probe.style.color = `var(${name})`;
  element.append(probe);
  const color = channels(getComputedStyle(probe).color);
  probe.remove();

  return color;
};

const toHex = ({ r, g, b, a }) => {
  const channel = (value) => Math.round(value * 255).toString(16).padStart(2, "0");
  const hex = `#${channel(r)}${channel(g)}${channel(b)}`;

  return a < 1 ? `${hex} ${Math.round(a * 100)}%` : hex;
};

const blend = (foreground, background) => ({
  r: foreground.r * foreground.a + background.r * (1 - foreground.a),
  g: foreground.g * foreground.a + background.g * (1 - foreground.a),
  b: foreground.b * foreground.a + background.b * (1 - foreground.a),
  a: 1,
});

const luminance = ({ r, g, b }) => {
  const linear = (channel) => (channel <= 0.03928 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4);

  return 0.2126 * linear(r) + 0.7152 * linear(g) + 0.0722 * linear(b);
};

const contrast = (foreground, background) => {
  const [light, dark] = [luminance(blend(foreground, background)), luminance(background)].sort((a, b) => b - a);

  return (light + 0.05) / (dark + 0.05);
};

const gradeFor = (ratio) => (ratio >= 7 ? "AAA" : ratio >= 4.5 ? "AA" : ratio >= 3 ? "AA large" : "fail");

const scaleOf = (name) => name.replace(/-+[^-]+$/, "");
const typeProperties = ["font-size", "font-family", "font-weight"];
const typeProperty = ({ name }) =>
  typeProperties.find((property) => usage.get(property)?.has(name)) ??
  typeProperties.find((property) => [...(usage.get(property) ?? [])].some((used) => scaleOf(used) === scaleOf(name)));

const kindOf = (name, value) => {
  if (CSS.supports("color", value)) return "colour";
  if (typeProperty({ name })) return "type";
  if (/^-?[\d.]+%?$/.test(value)) return "other";
  if (CSS.supports("transition-duration", value)) return "duration";
  if (/^(cubic-bezier|linear)\(/.test(value) && CSS.supports("transition-timing-function", value)) return "easing";
  if (CSS.supports("inline-size", value)) return "length";
  if (value !== "none" && CSS.supports("box-shadow", value)) return "shadow";
  return "other";
};

const root = document.documentElement;
const kinds = { colour: [], type: [], length: [], shadow: [], easing: [], duration: [], other: [] };

for (const [name, value] of declared) {
  if (isContextual(name)) continue;
  const resolved = valueOf(root, name);
  kinds[kindOf(name, resolved)].push({ name, value, resolved });
}

const groupBy = (tokens, keyOf) => {
  const groups = new Map();

  for (const token of tokens) {
    const key = keyOf(token);
    if (!groups.has(key)) groups.set(key, []);
    groups.get(key).push(token);
  }

  return groups;
};

const prefixOf = ({ name }) => `--${name.slice(2).split("-")[0]}`;
const familyOf = ({ name }) => (/-\d+$/.test(name) ? name.replace(/-\d+$/, "") : prefixOf({ name }));
const patternOf = ({ name, value }) => {
  if (referenceOf(value)) return `${prefixOf({ name })} aliases`;
  const [first, ...rest] = name.slice(2).split(/-+/);

  return `--${[first, ...rest.map(() => "*")].join("-")}`;
};

const breakable = (name) =>
  name
    .split(/(?<=[^-]-)/)
    .map((segment) => `<span class="playground-ui-tokens__segment">${segment}</span>`)
    .join("<wbr>");

const nameAndReference = ({ name, value }) => {
  const reference = referenceOf(value);

  return `<code>${breakable(name)}</code>${reference ? ` <code class="playground-ui-tokens__muted">${breakable(reference)}</code>` : ""}`;
};

const section = (title, body) => `
  <section class="playground-ui-tokens__section">
    <h2 class="playground-ui-tokens__title">${title}</h2>
    ${body}
  </section>`;

const groups = (map, render) =>
  [...map]
    .map(
      ([key, tokens]) => `
        <div class="playground-ui-tokens__group">
          <h3 class="playground-ui-tokens__group-title"><code>${escape(key)}</code></h3>
          ${render(tokens)}
        </div>`,
    )
    .join("");

const swatches = (tokens) => `
  <ul class="playground-ui-tokens__swatches">
    ${tokens
      .map(
        (token) => `
          <li class="playground-ui-tokens__swatch" data-token="${token.name}">
            <span class="playground-ui-tokens__chip" style="background-color: var(${token.name})"></span>
            <code>${token.name}</code>
            <code class="playground-ui-tokens__muted">${referenceOf(token.value) ? `${referenceOf(token.value)} / ` : ""}<span data-hex></span></code>
          </li>`,
      )
      .join("")}
  </ul>`;

const legend = (name) => `
  <p class="playground-ui-tokens__legend">
    <code>${escape(name)}</code>
    <code class="playground-ui-tokens__muted playground-ui-tokens__readout" aria-hidden="true"></code>
  </p>`;

const ramp = ([family, tokens]) => `
  <div class="playground-ui-tokens__group">
    <div class="playground-ui-tokens__ramp">
      ${tokens
        .map(
          (token) => `
            <button type="button" class="playground-ui-tokens__step" data-token="${token.name}" data-copy="var(${token.name})" style="background-color: var(${token.name})">
              <code>${escape(token.name.slice(family.length + 1))}</code>
            </button>`,
        )
        .join("")}
    </div>
    ${legend(`${family}-*`)}
  </div>`;

const squares = ([key, tokens]) => `
  <div class="playground-ui-tokens__group">
    <ul class="playground-ui-tokens__squares" data-sort>
      ${tokens
        .map(
          (token) => `
            <li>
              <button type="button" class="playground-ui-tokens__square-button" data-token="${token.name}" data-copy="var(${token.name})">
                <span class="playground-ui-tokens__square" style="inline-size: min(var(${token.name}), 6rem); block-size: min(var(${token.name}), 6rem)"></span>
                <span class="playground-ui-tokens__measure" style="inline-size: var(${token.name})" data-measure></span>
                <code class="playground-ui-tokens__muted" data-px></code>
              </button>
            </li>`,
        )
        .join("")}
    </ul>
    ${legend(key)}
  </div>`;

const bars = (tokens, unit) => `
  <ul class="playground-ui-tokens__bars" data-sort>
    ${tokens
      .map(
        (token) => `
          <li class="playground-ui-tokens__bar-row" data-token="${token.name}">
            <span class="playground-ui-tokens__bar-name">${nameAndReference(token)}</span>
            <span class="playground-ui-tokens__bar" style="inline-size: ${unit === "ms" ? `${durationOf(token.resolved) / 4}px` : `var(${token.name})`}" data-measure></span>
            <code class="playground-ui-tokens__muted" data-${unit}>${unit === "ms" ? `${durationOf(token.resolved)}ms` : ""}</code>
          </li>`,
      )
      .join("")}
  </ul>`;

const durationOf = (value) => parseFloat(value) * (value.endsWith("ms") ? 1 : 1000);

const samples = (tokens) => `
  <ul class="playground-ui-tokens__samples">
    ${tokens
      .map(
        (token) => `
          <li class="playground-ui-tokens__sample-row" data-token="${token.name}">
            <span class="playground-ui-tokens__meta">${nameAndReference(token)} <code class="playground-ui-tokens__muted" data-${typeProperty(token) === "font-size" ? "px" : "value"}>${typeProperty(token) === "font-size" ? "" : escape(token.resolved)}</code></span>
            <span class="playground-ui-tokens__sample" style="${typeProperty(token)}: var(${token.name})">Lorem ipsum</span>
          </li>`,
      )
      .join("")}
  </ul>`;

const shadows = (tokens) => `
  <ul class="playground-ui-tokens__shadows">
    ${tokens
      .map(
        (token) => `
          <li class="playground-ui-tokens__shadow-item">
            ${
              usage.get("text-shadow")?.has(token.name)
                ? `<span class="playground-ui-tokens__text-shadow" style="text-shadow: var(${token.name})">Lorem ipsum</span>`
                : `<span class="playground-ui-tokens__card" style="box-shadow: var(${token.name})"></span>`
            }
            <span>${nameAndReference(token)}</span>
          </li>`,
      )
      .join("")}
  </ul>`;

const pointsOf = (value) => {
  const numbers = value.match(/-?[\d.]+/g).map(Number);
  if (value.startsWith("cubic-bezier")) return { bezier: numbers };

  const stops = value
    .slice(7, -1)
    .split(",")
    .flatMap((stop) => {
      const [output, ...positions] = stop.trim().split(/\s+/).map(parseFloat);
      return positions.length ? positions.map((position) => ({ output, position: position / 100 })) : [{ output, position: null }];
    });

  stops[0].position ??= 0;
  stops.at(-1).position ??= 1;

  for (let index = 1; index < stops.length; index++) {
    if (stops[index].position !== null) continue;
    const next = stops.findIndex((stop, position) => position > index && stop.position !== null);
    const step = (stops[next].position - stops[index - 1].position) / (next - index + 1);
    stops[index].position = stops[index - 1].position + step;
  }

  return { stops };
};

const curve = (value) => {
  const { bezier, stops } = pointsOf(value);
  const path = bezier
    ? `M0,1 C${bezier[0]},${1 - bezier[1]} ${bezier[2]},${1 - bezier[3]} 1,0`
    : `M${stops.map(({ output, position }) => `${position},${1 - output}`).join(" L")}`;

  return `
    <svg class="playground-ui-tokens__curve" viewBox="-0.1 -0.3 1.2 1.6" aria-hidden="true">
      <path class="playground-ui-tokens__curve-frame" d="M0,0 H1 M0,1 H1" />
      <path d="${path}" />
    </svg>`;
};

const easings = (tokens) => `
  <ul class="playground-ui-tokens__easings">
    ${tokens
      .map(
        (token) => `
          <li class="playground-ui-tokens__easing">
            ${curve(token.resolved)}
            <span>${nameAndReference(token)}</span>
            <code class="playground-ui-tokens__muted">${escape(token.resolved)}</code>
          </li>`,
      )
      .join("")}
  </ul>`;

const table = (tokens) => `
  <div class="playground-ui-tokens__table" role="region" aria-label="Other tokens" tabindex="0">
    <table>
      <thead>
        <tr>
          <th scope="col">Token</th>
          <th scope="col">Value</th>
        </tr>
      </thead>
      <tbody>
        ${tokens
          .map(
            (token) => `
              <tr>
                <th scope="row">${nameAndReference(token)}</th>
                <td><code>${escape(token.resolved || token.value)}</code></td>
              </tr>`,
          )
          .join("")}
      </tbody>
    </table>
  </div>`;

const attributesOf = (attributes) =>
  Object.entries(attributes)
    .map(([attribute, value]) => ` ${attribute}="${value}"`)
    .join("");

const paint = surfaceToken && textToken ? ` style="background-color: var(${surfaceToken}); color: var(${textToken})"` : "";

const readability = (attributes) => {
  const probe = document.createElement("div");
  probe.innerHTML = `<div${attributesOf(attributes)}${paint}></div>`;
  container.append(probe);
  const ratio = contrast(colourIn(probe.firstChild, textToken), colourIn(probe.firstChild, surfaceToken));
  probe.remove();

  return ratio;
};

const partnerOf = (context) => {
  if (!paint || context.selector === ":root") return null;

  const hasSurface = context.tokens.has(surfaceToken);
  const hasText = context.tokens.has(textToken);
  if (hasSurface && hasText) return null;

  const wanted = hasSurface ? textToken : surfaceToken;
  const other = hasSurface ? surfaceToken : textToken;
  const ownAttribute = Object.keys(context.attributes)[0];
  const overlap = (candidate) => [...candidate.tokens.keys()].filter((name) => name !== wanted && context.tokens.has(name)).length;

  const candidates = [...contexts.values()]
    .filter((candidate) => candidate.selector !== ":root" && !(ownAttribute in candidate.attributes))
    .filter((candidate) => candidate.tokens.has(wanted) && !candidate.tokens.has(other))
    .map((candidate) => ({
      candidate,
      overlap: overlap(candidate),
      ratio: readability({ ...context.attributes, ...candidate.attributes }),
    }))
    .sort((a, b) => a.overlap - b.overlap || b.ratio - a.ratio);

  return candidates[0]?.candidate ?? null;
};

const band = (context) => {
  const partner = partnerOf(context);
  const attributes = { ...context.attributes, ...partner?.attributes };

  return `
    <div class="playground-ui-tokens__context"${attributesOf(attributes)}${paint}>
      <h4 class="playground-ui-tokens__context-title">
        <code>${escape(context.selector)}</code>
        ${partner ? `<span class="playground-ui-tokens__muted">on <code>${escape(partner.selector)}</code></span>` : ""}
      </h4>
      <ul class="playground-ui-tokens__roles">
        ${[...context.tokens]
          .map(
            ([name, value]) => `
              <li class="playground-ui-tokens__role" data-token="${name}">
                <span class="playground-ui-tokens__role-chip" style="background-color: var(${name})"></span>
                <code class="playground-ui-tokens__role-name">${name}</code>
                <code class="playground-ui-tokens__muted playground-ui-tokens__role-value">${referenceOf(value) ? `${referenceOf(value)} / ` : ""}<span data-hex>${escape(value)}</span></code>
                <code class="playground-ui-tokens__muted playground-ui-tokens__role-grade" data-grade></code>
              </li>`,
          )
          .join("")}
      </ul>
    </div>`;
};

const contextGroups = groupBy([...contexts.values()], (context) => Object.keys(context.attributes)[0] ?? ":root");

const typeGroups = groupBy(kinds.type, typeProperty);
const lengthGroups = new Map(
  [...groupBy(kinds.length, prefixOf)].flatMap(([prefix, tokens]) => {
    const patterns = groupBy(tokens, patternOf);
    return patterns.size > 1 ? [...patterns] : [[prefix, tokens]];
  }),
);


const renderers = {
  colour: swatches,
  type: samples,
  length: (tokens) => bars(tokens, "px"),
  shadow: shadows,
  easing: easings,
  duration: (tokens) => bars(tokens, "ms"),
  other: table,
};

const subjectOf = (selector) => {
  let depth = 0;
  let start = 0;

  for (let index = 0; index < selector.length; index++) {
    if ("([".includes(selector[index])) depth++;
    if (")]".includes(selector[index])) depth--;
    if (depth === 0 && " >+~".includes(selector[index])) start = index + 1;
  }

  return start;
};

const withoutNegations = (selector) => {
  let result = selector;
  let index;

  while ((index = result.indexOf(":not(")) !== -1) {
    let depth = 0;
    let end = index + 4;

    do {
      if (result[end] === "(") depth++;
      if (result[end] === ")") depth--;
      end++;
    } while (depth > 0);

    result = result.slice(0, index) + result.slice(end);
  }

  return result;
};

const isInteraction = (state) => {
  const interactions = /:(hover|focus-visible|focus-within|focus|active)(?![\w-])/g;
  const rest = withoutNegations(state);
  let remaining = rest.replace(interactions, "");
  while (/:(is|has|where)\([\s,]*\)/.test(remaining)) remaining = remaining.replace(/:(is|has|where)\([\s,]*\)/g, "");

  return remaining !== rest && remaining.trim() === "&";
};

const blocks = new Map();

for (const [selector, tokens] of scoped) {
  if (contexts.has(selector)) continue;

  const start = subjectOf(selector);
  const block = selector.slice(start).match(/^\.[\w-]+/)?.[0];
  if (!block) continue;

  const state = `${selector.slice(0, start)}&${selector.slice(start + block.length)}`;
  if (isInteraction(state)) continue;

  if (!blocks.has(block)) blocks.set(block, new Map([["&", new Map()]]));
  blocks.get(block).set(state, new Map([...(blocks.get(block).get(state) ?? []), ...tokens]));
}

const declarations = (tokens) => [...tokens].map(([name, value]) => `${name}: ${value}`).join("; ");
const styleOf = (tokens) => escape(declarations(tokens)).replace(/"/g, "&quot;");

const drawn = (tokens, inherited) => {
  const probe = document.createElement("div");
  probe.setAttribute("style", declarations(new Map([...inherited, ...tokens])));
  container.append(probe);

  const byKind = groupBy(
    [...tokens].map(([name, value]) => ({ name, value, resolved: valueOf(probe, name) })),
    (token) => kindOf(token.name, token.resolved),
  );
  probe.remove();

  return Object.keys(renderers)
    .filter((kind) => byKind.has(kind))
    .map((kind) => renderers[kind](byKind.get(kind)))
    .join("");
};

const component = ([block, states]) => {
  const base = states.get("&");

  return `
    <div class="playground-ui-tokens__group playground-ui-tokens__component" style="${styleOf(base)}">
      <h3 class="playground-ui-tokens__group-title"><code>${escape(block)}</code></h3>
      ${drawn(base, new Map())}
      ${[...states]
        .filter(([state]) => state !== "&")
        .map(
          ([state, tokens]) => `
            <div class="playground-ui-tokens__component playground-ui-tokens__state" style="${styleOf(tokens)}">
              <h4 class="playground-ui-tokens__group-title"><code>${escape(state)}</code></h4>
              ${drawn(tokens, base)}
            </div>`,
        )
        .join("")}
    </div>`;
};

container.innerHTML = [
  kinds.colour.length && section("Colours", [...groupBy(kinds.colour, familyOf)].map(ramp).join("")),
  contexts.size &&
    section(
      "Contexts",
      [...contextGroups]
        .map(
          ([attribute, members]) => `
            <div class="playground-ui-tokens__group">
              <h3 class="playground-ui-tokens__group-title"><code>${escape(attribute)}</code></h3>
              ${members.map(band).join("")}
            </div>`,
        )
        .join(""),
    ),
  kinds.type.length && section("Type", groups(typeGroups, samples)),
  kinds.length.length && section("Lengths", [...lengthGroups].map(squares).join("")),
  kinds.shadow.length && section("Shadows", groups(groupBy(kinds.shadow, prefixOf), shadows)),
  kinds.easing.length && section("Easings", groups(groupBy(kinds.easing, prefixOf), easings)),
  kinds.duration.length && section("Durations", groups(groupBy(kinds.duration, prefixOf), (tokens) => bars(tokens, "ms"))),
  kinds.other.length && section("Other", groups(groupBy(kinds.other, prefixOf), table)),
  blocks.size && section("Components", [...blocks].map(component).join("")),
]
  .filter(Boolean)
  .join("");

const pageBackground = surfaceToken ? colourIn(container, surfaceToken) : channels(getComputedStyle(document.body).backgroundColor);

for (const swatch of container.querySelectorAll(".playground-ui-tokens__swatch")) {
  const color = colourIn(swatch, swatch.dataset.token);
  swatch.querySelector("[data-hex]").textContent = toHex(color);
  if (toHex(blend(color, pageBackground)) === toHex(pageBackground)) swatch.querySelector(".playground-ui-tokens__chip").dataset.faint = "";
}

for (const context of container.querySelectorAll(".playground-ui-tokens__context")) {
  const background = surfaceToken ? colourIn(context, surfaceToken) : pageBackground;

  for (const role of context.querySelectorAll(".playground-ui-tokens__role")) {
    const name = role.dataset.token;
    const value = valueOf(context, name);
    const chip = role.querySelector(".playground-ui-tokens__role-chip");

    if (!CSS.supports("color", value)) {
      chip.style.visibility = "hidden";
      role.querySelector("[data-hex]").textContent = value;
      continue;
    }

    const color = colourIn(context, name);
    role.querySelector("[data-hex]").textContent = toHex(color);

    if (toHex(blend(color, background)) === toHex(background)) {
      chip.dataset.faint = "";
      role.querySelector("[data-grade]").textContent = "n/a";
      continue;
    }

    const ratio = contrast(color, background);
    const grade = gradeFor(ratio);
    role.querySelector("[data-grade]").textContent = `${ratio.toFixed(2)} · ${grade}`;
    role.querySelector("[data-grade]").dataset.grade = grade;
  }
}

for (const step of container.querySelectorAll(".playground-ui-tokens__step")) {
  const color = colourIn(step, step.dataset.token);
  const shown = blend(color, pageBackground);
  const black = { r: 0, g: 0, b: 0, a: 1 };
  const white = { r: 1, g: 1, b: 1, a: 1 };

  step.style.color = contrast(black, shown) > contrast(white, shown) ? "#000" : "#fff";
  step.dataset.label = `${step.dataset.token} ${toHex(color)}`;
  step.setAttribute("aria-label", step.dataset.label);
  if (toHex(shown) === toHex(pageBackground)) step.dataset.faint = "";
}

for (const list of container.querySelectorAll("[data-sort]")) {
  const width = (row) => row.querySelector("[data-measure]").getBoundingClientRect().width;
  list.append(...[...list.children].sort((a, b) => width(a) - width(b)));
}

const showPixels = () => {
  for (const output of container.querySelectorAll("[data-px]")) {
    const row = output.closest("[data-token]");
    const measured = row.querySelector("[data-measure]")?.getBoundingClientRect().width ?? parseFloat(getComputedStyle(row.querySelector(".playground-ui-tokens__sample")).fontSize);
    output.textContent = `${Math.round(measured)}px`;

    if (row.matches("[data-copy]")) {
      row.dataset.label = `${row.dataset.token} ${output.textContent}`;
      row.setAttribute("aria-label", row.dataset.label);
    }
  }
};

showPixels();
addEventListener("resize", showPixels);

container.insertAdjacentHTML("beforeend", `<p class="playground-ui-tokens__status" role="status"></p>`);
const status = container.querySelector(".playground-ui-tokens__status");

const readoutOf = (button) => button.closest(".playground-ui-tokens__group").querySelector(".playground-ui-tokens__readout");

for (const type of ["mouseover", "focusin"]) {
  container.addEventListener(type, (event) => {
    const button = event.target.closest("[data-copy]");
    if (button) readoutOf(button).textContent = button.dataset.label;
  });
}

for (const type of ["mouseout", "focusout"]) {
  container.addEventListener(type, (event) => {
    const button = event.target.closest("[data-copy]");
    if (button && !button.contains(event.relatedTarget)) readoutOf(button).textContent = "";
  });
}

container.addEventListener("click", async (event) => {
  const button = event.target.closest("[data-copy]");
  if (!button) return;

  await copy(button.dataset.copy);
  readoutOf(button).textContent = `${button.dataset.label} · Copied ${button.dataset.copy}`;
  const message = `Copied ${button.dataset.copy}`;
  status.textContent = status.textContent === message ? `${message}.` : message;
});
