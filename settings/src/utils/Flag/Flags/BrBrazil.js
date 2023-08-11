import * as React from "react";
const SvgBrBrazil = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="BR_-_Brazil_svg__a"
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
    <g mask="url(#BR_-_Brazil_svg__a)">
      <path
        fill="#093"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="BR_-_Brazil_svg__b"
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
      <g mask="url(#BR_-_Brazil_svg__b)">
        <g
          fillRule="evenodd"
          clipRule="evenodd"
          filter="url(#BR_-_Brazil_svg__c)"
        >
          <path
            fill="#FFD221"
            d="m7.963 1.852 6.101 4.252-6.184 3.982L1.904 6.02l6.06-4.169Z"
          />
          <path
            fill="url(#BR_-_Brazil_svg__d)"
            d="m7.963 1.852 6.101 4.252-6.184 3.982L1.904 6.02l6.06-4.169Z"
          />
        </g>
        <path
          fill="#2E42A5"
          fillRule="evenodd"
          d="M8 8.6a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"
          clipRule="evenodd"
        />
        <mask
          id="BR_-_Brazil_svg__e"
          width={6}
          height={6}
          x={5}
          y={3}
          maskUnits="userSpaceOnUse"
          style={{
            maskType: "luminance",
          }}
        >
          <path
            fill="#fff"
            fillRule="evenodd"
            d="M8 8.6a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"
            clipRule="evenodd"
          />
        </mask>
        <g fill="#F7FCFF" mask="url(#BR_-_Brazil_svg__e)">
          <path
            fillRule="evenodd"
            d="m7.19 7.285-.112.059.022-.125-.09-.088.124-.018L7.19 7l.056.113.125.018-.09.088.02.125-.111-.059ZM8.19 7.285l-.112.059.022-.125-.09-.088.124-.018L8.19 7l.056.113.125.018-.09.088.02.125-.111-.059ZM8.19 7.885l-.112.059.022-.125-.09-.088.124-.018.056-.113.056.113.125.018-.09.088.02.125-.111-.059ZM7.69 5.785l-.112.059.022-.125-.09-.088.124-.018.056-.113.056.113.125.018-.09.088.02.125-.111-.059ZM7.69 6.785l-.112.059.022-.125-.09-.088.124-.018.056-.113.056.113.125.018-.09.088.02.125-.111-.059ZM6.99 6.285l-.112.059.022-.125-.09-.088.124-.018L6.99 6l.056.113.125.018-.09.088.02.125-.11-.059ZM6.29 6.685l-.112.059.022-.125-.09-.088.124-.018.056-.113.056.113.125.018-.09.088.02.125-.111-.059ZM8.59 4.985l-.112.059.022-.125-.09-.088.124-.018.056-.113.056.113.125.018-.09.088.02.125-.111-.059Z"
            clipRule="evenodd"
          />
          <path d="m4.962 5.499.076-.998c2.399.181 4.292.97 5.656 2.373l-.717.697C8.795 6.355 7.131 5.662 4.962 5.5Z" />
        </g>
      </g>
    </g>
    <defs>
      <linearGradient
        id="BR_-_Brazil_svg__d"
        x1={16}
        x2={16}
        y1={12}
        y2={0}
        gradientUnits="userSpaceOnUse"
      >
        <stop stopColor="#FFC600" />
        <stop offset={1} stopColor="#FFDE42" />
      </linearGradient>
      <filter
        id="BR_-_Brazil_svg__c"
        width={12.16}
        height={8.234}
        x={1.904}
        y={1.852}
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
        <feColorMatrix values="0 0 0 0 0.0313726 0 0 0 0 0.368627 0 0 0 0 0 0 0 0 0.28 0" />
        <feBlend
          in2="BackgroundImageFix"
          result="effect1_dropShadow_270_54984"
        />
        <feBlend
          in="SourceGraphic"
          in2="effect1_dropShadow_270_54984"
          result="shape"
        />
      </filter>
    </defs>
  </svg>
);
export default SvgBrBrazil;
