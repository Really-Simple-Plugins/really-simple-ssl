import * as React from "react";
const SvgAqAntarctica = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="AQ_-_Antarctica_svg__a"
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
    <g mask="url(#AQ_-_Antarctica_svg__a)">
      <path
        fill="#5196ED"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="AQ_-_Antarctica_svg__b"
        width={16}
        height={12}
        x={0}
        y={0}
        maskUnits="userSpaceOnUse"
        style={{
          maskType: "luminance",
        }}
      >
        <path
          fill="#fff"
          fillRule="evenodd"
          d="M0 0v12h16V0H0Z"
          clipRule="evenodd"
        />
      </mask>
      <g
        fillRule="evenodd"
        clipRule="evenodd"
        filter="url(#AQ_-_Antarctica_svg__c)"
        mask="url(#AQ_-_Antarctica_svg__b)"
      >
        <path
          fill="#fff"
          d="M4.359 3.947s1.032.566 1.175.723c.144.156.374.732.732.406.36-.325.718-.072.718-.59s.535-1.72 1.312-1.419c.777.301 1.416.113 1.596.233.179.12.61.719.944.719.335 0 .502.35.526.855.024.506-.107.555.203.615.311.06.43.29.276.59-.156.302-.144.17-.12.482.024.314-.31 2.161-1.34 2.342-1.028.18-2.002.084-1.74-.265.264-.35.628-.752.09-.836-.538-.084-.877-.156-1.415-.012-.538.145-1.1.374-1.435-.06-.335-.434-.263-.747-.538-.952-.275-.205-.61-.168-.335-.566.275-.398.502-.25.275-.562C5.056 5.336 4.2 5.2 4.2 4.887c0-.314-.546-1 .16-.94Z"
        />
        <path
          fill="#F5F8FB"
          d="M4.359 3.947s1.032.566 1.175.723c.144.156.374.732.732.406.36-.325.718-.072.718-.59s.535-1.72 1.312-1.419c.777.301 1.416.113 1.596.233.179.12.61.719.944.719.335 0 .502.35.526.855.024.506-.107.555.203.615.311.06.43.29.276.59-.156.302-.144.17-.12.482.024.314-.31 2.161-1.34 2.342-1.028.18-2.002.084-1.74-.265.264-.35.628-.752.09-.836-.538-.084-.877-.156-1.415-.012-.538.145-1.1.374-1.435-.06-.335-.434-.263-.747-.538-.952-.275-.205-.61-.168-.335-.566.275-.398.502-.25.275-.562C5.056 5.336 4.2 5.2 4.2 4.887c0-.314-.546-1 .16-.94Z"
        />
      </g>
    </g>
    <defs>
      <filter
        id="AQ_-_Antarctica_svg__c"
        width={8.511}
        height={6.573}
        x={3.698}
        y={2.719}
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
        <feGaussianBlur stdDeviation={0.15} />
        <feColorMatrix values="0 0 0 0 0.0941176 0 0 0 0 0.32549 0 0 0 0 0.639216 0 0 0 0.43 0" />
        <feBlend
          in2="BackgroundImageFix"
          result="effect1_dropShadow_270_54950"
        />
        <feBlend
          in="SourceGraphic"
          in2="effect1_dropShadow_270_54950"
          result="shape"
        />
      </filter>
    </defs>
  </svg>
);
export default SvgAqAntarctica;
