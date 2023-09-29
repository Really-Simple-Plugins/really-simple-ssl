import * as React from "react";
const SvgCzCzechRepublic = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="CZ_-_Czech_Republic_svg__a"
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
    <g mask="url(#CZ_-_Czech_Republic_svg__a)">
      <path
        fill="#E31D1C"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="CZ_-_Czech_Republic_svg__b"
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
      <g mask="url(#CZ_-_Czech_Republic_svg__b)">
        <path
          fill="#F7FCFF"
          fillRule="evenodd"
          d="M0-1v7h16v-7H0Z"
          clipRule="evenodd"
        />
      </g>
      <path
        fill="#3D58DB"
        fillRule="evenodd"
        d="M0 0v12l9-6-9-6Z"
        clipRule="evenodd"
      />
      <mask
        id="CZ_-_Czech_Republic_svg__c"
        width={9}
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
          d="M0 0v12l9-6-9-6Z"
          clipRule="evenodd"
        />
      </mask>
    </g>
  </svg>
);
export default SvgCzCzechRepublic;
