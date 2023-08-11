import * as React from "react";
const SvgBtBhutan = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="BT_-_Bhutan_svg__a"
      width={16}
      height={12}
      x={0}
      y={0}
      maskUnits="userSpaceOnUse"
      style={{
        maskType: "luminance",
      }}
    >
      <path fill="#fff" d="M0 0h16v12H0z" />
    </mask>
    <g mask="url(#BT_-_Bhutan_svg__a)">
      <path
        fill="#FF6230"
        fillRule="evenodd"
        d="M0 0h16v12H0V0Z"
        clipRule="evenodd"
      />
      <path
        fill="#FECA00"
        fillRule="evenodd"
        d="M0 0v12L16 0H0Z"
        clipRule="evenodd"
      />
      <g filter="url(#BT_-_Bhutan_svg__b)">
        <path
          fill="#fff"
          fillRule="evenodd"
          d="M2.191 8.164s-.751.685-.156.721c.596.036.485.4.972.002.488-.397.015.063.665-.027s.975-.883 1.318-.829c.343.054.144-.072.433.27.29.344.797-.385.526.121-.27.506-.379 1.011.018.993.397-.018.56-.253.74-.505.181-.253 1.59.614.94-.253-.65-.867-.903.095-.957-.357-.054-.451-.183-.686.124-.47.307.218 1.03.163.47-.378-.56-.542-.483-.686-.645-.542-.163.144-.56-.542 0-.632.56-.09 1.204.217 1.42.38.218.162.886-.037 1.03.288.145.325.632.38.759.578.126.199.036 1.174 1.065 1.12 1.03-.055 1.354-.578.921-.994-.434-.415-.397-1.173-.849-.812-.451.36-1.101.307-1.101-.127 0-.433.198-.505.162-.848-.036-.344-.072-.2.596-.181.668.018.434 0 .921-.235.488-.234.813.795 1.011.036.199-.758-.072-1.535-.614-1.228-.541.307-.523 1.066-1.21.47-.686-.596-1.01-.289-.794-.614.217-.325-.054-.56.433-.343.488.217.344.289.759.343.415.054 2.618.325 2.13-.126-.487-.452-.955-.378-1.027-.667-.072-.29.225-.217.658-.38.434-.162.253-.993-.163-.83-.415.162-.38.885-1.228.433-.974.424-.823.211-1.319-.507-.433-.343-.668-.36-1.408.036-.585.241-1.074.687-.767 1.283.308.595 1.039 1.718.642 1.79-.398.072-1.824-.993-2.655-.433-.83.56-1.284 1.104-1.555 1.754-.27.65-1.147 1.029-1.364 1.029-.217 0-.522.382-.9.671Z"
          clipRule="evenodd"
        />
      </g>
    </g>
    <defs>
      <filter
        id="BT_-_Bhutan_svg__b"
        width={15.543}
        height={11.641}
        x={-0.194}
        y={-0.225}
        colorInterpolationFilters="sRGB"
        filterUnits="userSpaceOnUse"
      >
        <feFlood floodOpacity={0} result="BackgroundImageFix" />
        <feColorMatrix
          in="SourceAlpha"
          result="hardAlpha"
          values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"
        />
        <feOffset />
        <feGaussianBlur stdDeviation={1} />
        <feColorMatrix values="0 0 0 0 0.866667 0 0 0 0 0.184314 0 0 0 0 0 0 0 0 0.38 0" />
        <feBlend
          in2="BackgroundImageFix"
          result="effect1_dropShadow_270_55195"
        />
        <feBlend
          in="SourceGraphic"
          in2="effect1_dropShadow_270_55195"
          result="shape"
        />
      </filter>
    </defs>
  </svg>
);
export default SvgBtBhutan;
